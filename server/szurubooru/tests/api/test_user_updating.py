import datetime
import pytest
from szurubooru import api, config, db, errors
from szurubooru.util import auth, misc, users

EMPTY_PIXEL = \
    b'\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x01\x00\x00\x00\x00' \
    b'\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x01\x00\x2c\x00\x00\x00\x00' \
    b'\x01\x00\x01\x00\x00\x02\x02\x4c\x01\x00\x3b'

def get_user(name):
    return db.session.query(db.User).filter_by(name=name).first()

@pytest.fixture
def test_ctx(config_injector, context_factory, user_factory):
    config_injector({
        'secret': '',
        'user_name_regex': '^[^!]{3,}$',
        'password_regex': '^[^!]{3,}$',
        'thumbnails': {'avatar_width': 200, 'avatar_height': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {},
        'privileges': {
            'users:edit:self:name': 'regular_user',
            'users:edit:self:pass': 'regular_user',
            'users:edit:self:email': 'regular_user',
            'users:edit:self:rank': 'mod',
            'users:edit:self:avatar': 'mod',
            'users:edit:any:name': 'mod',
            'users:edit:any:pass': 'mod',
            'users:edit:any:email': 'mod',
            'users:edit:any:rank': 'admin',
            'users:edit:any:avatar': 'admin',
        },
    })
    ret = misc.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.api = api.UserDetailApi()
    return ret

def test_updating_user(test_ctx):
    user = test_ctx.user_factory(name='u1', rank='admin')
    db.session.add(user)
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={
                'name': 'chewie',
                'email': 'asd@asd.asd',
                'password': 'oks',
                'rank': 'mod',
                'avatarStyle': 'gravatar',
            },
            user=user),
        'u1')
    assert result == {
        'user': {
            'avatarStyle': 'gravatar',
            'avatarUrl': 'http://gravatar.com/avatar/' +
                '6f370c8c7109534c3d5c394123a477d7?d=retro&s=200',
            'creationTime': datetime.datetime(1997, 1, 1),
            'lastLoginTime': None,
            'email': 'asd@asd.asd',
            'name': 'chewie',
            'rank': 'mod',
            'rankName': 'Unknown',
        }
    }
    user = get_user('chewie')
    assert user.name == 'chewie'
    assert user.email == 'asd@asd.asd'
    assert user.rank == 'mod'
    assert user.avatar_style == user.AVATAR_GRAVATAR
    assert auth.is_valid_password(user, 'oks') is True
    assert auth.is_valid_password(user, 'invalid') is False

@pytest.mark.parametrize('input,expected_exception', [
    ({'name': None}, users.InvalidUserNameError),
    ({'name': ''}, users.InvalidUserNameError),
    ({'name': '!bad'}, users.InvalidUserNameError),
    ({'name': 'x' * 51}, users.InvalidUserNameError),
    ({'password': None}, users.InvalidPasswordError),
    ({'password': ''}, users.InvalidPasswordError),
    ({'password': '!bad'}, users.InvalidPasswordError),
    ({'rank': None}, users.InvalidRankError),
    ({'rank': ''}, users.InvalidRankError),
    ({'rank': 'bad'}, users.InvalidRankError),
    ({'email': 'bad'}, users.InvalidEmailError),
    ({'email': 'x@' * 65 + '.com'}, users.InvalidEmailError),
    ({'avatarStyle': None}, users.InvalidAvatarError),
    ({'avatarStyle': ''}, users.InvalidAvatarError),
    ({'avatarStyle': 'invalid'}, users.InvalidAvatarError),
    ({'avatarStyle': 'manual'}, users.InvalidAvatarError), # missing file
])
def test_trying_to_pass_invalid_input(test_ctx, input, expected_exception):
    user = test_ctx.user_factory(name='u1', rank='admin')
    db.session.add(user)
    with pytest.raises(expected_exception):
        test_ctx.api.put(
            test_ctx.context_factory(input=input, user=user), 'u1')

@pytest.mark.parametrize(
    'field', ['name', 'email', 'password', 'rank', 'avatarStyle'])
def test_omitting_optional_field(test_ctx, tmpdir, field):
    config.config['data_dir'] = str(tmpdir.mkdir('data'))
    config.config['data_url'] = 'http://example.com/data/'
    user = test_ctx.user_factory(name='u1', rank='admin')
    db.session.add(user)
    input = {
        'name': 'chewie',
        'email': 'asd@asd.asd',
        'password': 'oks',
        'rank': 'mod',
        'avatarStyle': 'gravatar',
    }
    del input[field]
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input=input,
            files={'avatar': EMPTY_PIXEL},
            user=user),
        'u1')
    assert result is not None

def test_trying_to_update_non_existing(test_ctx):
    user = test_ctx.user_factory(name='u1', rank='admin')
    db.session.add(user)
    with pytest.raises(users.UserNotFoundError):
        test_ctx.api.put(test_ctx.context_factory(user=user), 'u2')

def test_removing_email(test_ctx):
    user = test_ctx.user_factory(name='u1', rank='admin')
    db.session.add(user)
    test_ctx.api.put(
        test_ctx.context_factory(input={'email': ''}, user=user), 'u1')
    assert get_user('u1').email is None

@pytest.mark.parametrize('input', [
    {'name': 'whatever'},
    {'email': 'whatever'},
    {'rank': 'whatever'},
    {'password': 'whatever'},
    {'avatarStyle': 'whatever'},
])
def test_trying_to_update_someone_else(test_ctx, input):
    user1 = test_ctx.user_factory(name='u1', rank='regular_user')
    user2 = test_ctx.user_factory(name='u2', rank='regular_user')
    db.session.add_all([user1, user2])
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(
            test_ctx.context_factory(input=input, user=user1), user2.name)

def test_trying_to_become_someone_else(test_ctx):
    user1 = test_ctx.user_factory(name='me', rank='regular_user')
    user2 = test_ctx.user_factory(name='her', rank='regular_user')
    db.session.add_all([user1, user2])
    with pytest.raises(users.UserAlreadyExistsError):
        test_ctx.api.put(
            test_ctx.context_factory(input={'name': 'her'}, user=user1),
            'me')
    with pytest.raises(users.UserAlreadyExistsError):
        test_ctx.api.put(
            test_ctx.context_factory(input={'name': 'HER'}, user=user1), 'me')

def test_mods_trying_to_become_admin(test_ctx):
    user1 = test_ctx.user_factory(name='u1', rank='mod')
    user2 = test_ctx.user_factory(name='u2', rank='mod')
    db.session.add_all([user1, user2])
    context = test_ctx.context_factory(input={'rank': 'admin'}, user=user1)
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(context, user1.name)
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(context, user2.name)

def test_uploading_avatar(test_ctx, tmpdir):
    config.config['data_dir'] = str(tmpdir.mkdir('data'))
    config.config['data_url'] = 'http://example.com/data/'
    user = test_ctx.user_factory(name='u1', rank='mod')
    db.session.add(user)
    response = test_ctx.api.put(
        test_ctx.context_factory(
            input={'avatarStyle': 'manual'},
            files={'avatar': EMPTY_PIXEL},
            user=user),
        'u1')
    user = get_user('u1')
    assert user.avatar_style == user.AVATAR_MANUAL
    assert response['user']['avatarUrl'] == \
        'http://example.com/data/avatars/u1.jpg'
