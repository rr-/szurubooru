import datetime
import pytest
from szurubooru import api, config, db, errors
from szurubooru.func import auth, util, users

EMPTY_PIXEL = \
    b'\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x01\x00\x00\x00\x00' \
    b'\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x01\x00\x2c\x00\x00\x00\x00' \
    b'\x01\x00\x01\x00\x00\x02\x02\x4c\x01\x00\x3b'

@pytest.fixture
def test_ctx(tmpdir, config_injector, context_factory, user_factory):
    config_injector({
        'secret': '',
        'user_name_regex': '^[^!]{3,}$',
        'password_regex': '^[^!]{3,}$',
        'thumbnails': {'avatar_width': 200, 'avatar_height': 200},
        'privileges': {
            'users:edit:self:name': db.User.RANK_REGULAR,
            'users:edit:self:pass': db.User.RANK_REGULAR,
            'users:edit:self:email': db.User.RANK_REGULAR,
            'users:edit:self:rank': db.User.RANK_MODERATOR,
            'users:edit:self:avatar': db.User.RANK_MODERATOR,
            'users:edit:any:name': db.User.RANK_MODERATOR,
            'users:edit:any:pass': db.User.RANK_MODERATOR,
            'users:edit:any:email': db.User.RANK_MODERATOR,
            'users:edit:any:rank': db.User.RANK_ADMINISTRATOR,
            'users:edit:any:avatar': db.User.RANK_ADMINISTRATOR,
        },
        'data_dir': str(tmpdir.mkdir('data')),
        'data_url': 'http://example.com/data/',
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.api = api.UserDetailApi()
    return ret

def test_updating_user(test_ctx):
    user = test_ctx.user_factory(name='u1', rank=db.User.RANK_ADMINISTRATOR)
    db.session.add(user)
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={
                'version': 1,
                'name': 'chewie',
                'email': 'asd@asd.asd',
                'password': 'oks',
                'rank': 'moderator',
                'avatarStyle': 'gravatar',
            },
            user=user),
        'u1')
    assert result == {
        'avatarStyle': 'gravatar',
        'avatarUrl': 'https://gravatar.com/avatar/' +
            '6f370c8c7109534c3d5c394123a477d7?d=retro&s=200',
        'creationTime': datetime.datetime(1997, 1, 1),
        'lastLoginTime': None,
        'email': 'asd@asd.asd',
        'name': 'chewie',
        'rank': 'moderator',
        'commentCount': 0,
        'likedPostCount': 0,
        'dislikedPostCount': 0,
        'favoritePostCount': 0,
        'uploadedPostCount': 0,
        'version': 2,
    }
    user = users.get_user_by_name('chewie')
    assert user.name == 'chewie'
    assert user.email == 'asd@asd.asd'
    assert user.rank == db.User.RANK_MODERATOR
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
    ({'rank': 'anonymous'}, users.InvalidRankError),
    ({'rank': 'nobody'}, users.InvalidRankError),
    ({'email': 'bad'}, users.InvalidEmailError),
    ({'email': 'x@' * 65 + '.com'}, users.InvalidEmailError),
    ({'avatarStyle': None}, users.InvalidAvatarError),
    ({'avatarStyle': ''}, users.InvalidAvatarError),
    ({'avatarStyle': 'invalid'}, users.InvalidAvatarError),
    ({'avatarStyle': 'manual'}, users.InvalidAvatarError), # missing file
])
def test_trying_to_pass_invalid_input(test_ctx, input, expected_exception):
    user = test_ctx.user_factory(name='u1', rank=db.User.RANK_ADMINISTRATOR)
    db.session.add(user)
    with pytest.raises(expected_exception):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={**input, **{'version': 1}},
                user=user),
            'u1')

@pytest.mark.parametrize(
    'field', ['name', 'email', 'password', 'rank', 'avatarStyle'])
def test_omitting_optional_field(test_ctx, field):
    user = test_ctx.user_factory(name='u1', rank=db.User.RANK_ADMINISTRATOR)
    db.session.add(user)
    input = {
        'name': 'chewie',
        'email': 'asd@asd.asd',
        'password': 'oks',
        'rank': 'moderator',
        'avatarStyle': 'gravatar',
    }
    del input[field]
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={**input, **{'version': 1}},
            files={'avatar': EMPTY_PIXEL},
            user=user),
        'u1')
    assert result is not None

def test_trying_to_update_non_existing(test_ctx):
    user = test_ctx.user_factory(name='u1', rank=db.User.RANK_ADMINISTRATOR)
    db.session.add(user)
    with pytest.raises(users.UserNotFoundError):
        test_ctx.api.put(test_ctx.context_factory(user=user), 'u2')

def test_removing_email(test_ctx):
    user = test_ctx.user_factory(name='u1', rank=db.User.RANK_ADMINISTRATOR)
    db.session.add(user)
    test_ctx.api.put(
        test_ctx.context_factory(
            input={'email': '', 'version': 1}, user=user), 'u1')
    assert users.get_user_by_name('u1').email is None

@pytest.mark.parametrize('input', [
    {'name': 'whatever'},
    {'email': 'whatever'},
    {'rank': 'whatever'},
    {'password': 'whatever'},
    {'avatarStyle': 'whatever'},
])
def test_trying_to_update_someone_else(test_ctx, input):
    user1 = test_ctx.user_factory(name='u1', rank=db.User.RANK_REGULAR)
    user2 = test_ctx.user_factory(name='u2', rank=db.User.RANK_REGULAR)
    db.session.add_all([user1, user2])
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={**input, **{'version': 1}},
                user=user1),
            user2.name)

def test_trying_to_become_someone_else(test_ctx):
    user1 = test_ctx.user_factory(name='me', rank=db.User.RANK_REGULAR)
    user2 = test_ctx.user_factory(name='her', rank=db.User.RANK_REGULAR)
    db.session.add_all([user1, user2])
    with pytest.raises(users.UserAlreadyExistsError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'name': 'her', 'version': 1}, user=user1),
            'me')
    with pytest.raises(users.UserAlreadyExistsError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'name': 'HER', 'version': 1}, user=user1),
            'me')

def test_trying_to_make_someone_into_someone_else(test_ctx):
    user1 = test_ctx.user_factory(name='him', rank=db.User.RANK_REGULAR)
    user2 = test_ctx.user_factory(name='her', rank=db.User.RANK_REGULAR)
    user3 = test_ctx.user_factory(name='me', rank=db.User.RANK_MODERATOR)
    db.session.add_all([user1, user2, user3])
    with pytest.raises(users.UserAlreadyExistsError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'name': 'her', 'version': 1}, user=user3),
            'him')
    with pytest.raises(users.UserAlreadyExistsError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'name': 'HER', 'version': 1}, user=user3),
            'him')

def test_renaming_someone_else(test_ctx):
    user1 = test_ctx.user_factory(name='him', rank=db.User.RANK_REGULAR)
    user2 = test_ctx.user_factory(name='me', rank=db.User.RANK_MODERATOR)
    db.session.add_all([user1, user2])
    test_ctx.api.put(
        test_ctx.context_factory(
            input={'name': 'himself', 'version': 1}, user=user2),
        'him')
    test_ctx.api.put(
        test_ctx.context_factory(
            input={'name': 'HIMSELF', 'version': 2}, user=user2),
        'himself')

def test_mods_trying_to_become_admin(test_ctx):
    user1 = test_ctx.user_factory(name='u1', rank=db.User.RANK_MODERATOR)
    user2 = test_ctx.user_factory(name='u2', rank=db.User.RANK_MODERATOR)
    db.session.add_all([user1, user2])
    context = test_ctx.context_factory(
        input={'rank': 'administrator', 'version': 1},
        user=user1)
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(context, user1.name)
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(context, user2.name)

def test_uploading_avatar(test_ctx):
    user = test_ctx.user_factory(name='u1', rank=db.User.RANK_MODERATOR)
    db.session.add(user)
    response = test_ctx.api.put(
        test_ctx.context_factory(
            input={'avatarStyle': 'manual', 'version': 1},
            files={'avatar': EMPTY_PIXEL},
            user=user),
        'u1')
    user = users.get_user_by_name('u1')
    assert user.avatar_style == user.AVATAR_MANUAL
    assert response['avatarUrl'] == \
        'http://example.com/data/avatars/u1.png'
