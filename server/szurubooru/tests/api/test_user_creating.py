import datetime
import pytest
from szurubooru import api, config, db, errors
from szurubooru.func import auth, util, users

EMPTY_PIXEL = \
    b'\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x01\x00\x00\x00\x00' \
    b'\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x01\x00\x2c\x00\x00\x00\x00' \
    b'\x01\x00\x01\x00\x00\x02\x02\x4c\x01\x00\x3b'

@pytest.fixture
def test_ctx(config_injector, context_factory, user_factory):
    config_injector({
        'secret': '',
        'user_name_regex': '[^!]{3,}',
        'password_regex': '[^!]{3,}',
        'default_rank': db.User.RANK_REGULAR,
        'thumbnails': {'avatar_width': 200, 'avatar_height': 200},
        'privileges': {'users:create': 'anonymous'},
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.api = api.UserListApi()
    return ret

def test_creating_user(test_ctx, fake_datetime):
    with fake_datetime('1969-02-12'):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'name': 'chewie1',
                    'email': 'asd@asd.asd',
                    'password': 'oks',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert result == {
        'user': {
            'avatarStyle': 'gravatar',
            'avatarUrl': 'http://gravatar.com/avatar/' +
                '6f370c8c7109534c3d5c394123a477d7?d=retro&s=200',
            'creationTime': datetime.datetime(1969, 2, 12),
            'lastLoginTime': None,
            'name': 'chewie1',
            'rank': 'administrator',
            'email': 'asd@asd.asd',
        }
    }
    user = users.get_user_by_name('chewie1')
    assert user.name == 'chewie1'
    assert user.email == 'asd@asd.asd'
    assert user.rank == db.User.RANK_ADMINISTRATOR
    assert auth.is_valid_password(user, 'oks') is True
    assert auth.is_valid_password(user, 'invalid') is False

def test_first_user_becomes_admin_others_not(test_ctx):
    result1 = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'name': 'chewie1',
                'email': 'asd@asd.asd',
                'password': 'oks',
            },
            user=test_ctx.user_factory(rank='anonymous')))
    result2 = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'name': 'chewie2',
                'email': 'asd@asd.asd',
                'password': 'sok',
            },
            user=test_ctx.user_factory(rank='anonymous')))
    assert result1['user']['rank'] == 'administrator'
    assert result2['user']['rank'] == 'regular'
    first_user = users.get_user_by_name('chewie1')
    other_user = users.get_user_by_name('chewie2')
    assert first_user.rank == db.User.RANK_ADMINISTRATOR
    assert other_user.rank == db.User.RANK_REGULAR

def test_first_user_does_not_become_admin_if_they_dont_wish_so(test_ctx):
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'name': 'chewie1',
                'email': 'asd@asd.asd',
                'password': 'oks',
                'rank': 'regular',
            },
            user=test_ctx.user_factory(rank='anonymous')))
    assert result['user']['rank'] == 'regular'

def test_trying_to_become_someone_else(test_ctx):
    test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'name': 'chewie',
                'email': 'asd@asd.asd',
                'password': 'oks',
            },
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    with pytest.raises(users.UserAlreadyExistsError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'name': 'chewie',
                    'email': 'asd@asd.asd',
                    'password': 'oks',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    with pytest.raises(users.UserAlreadyExistsError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'name': 'CHEWIE',
                    'email': 'asd@asd.asd',
                    'password': 'oks',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

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
    real_input={
        'name': 'chewie',
        'email': 'asd@asd.asd',
        'password': 'oks',
    }
    for key, value in input.items():
        real_input[key] = value
    with pytest.raises(expected_exception):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=real_input,
                user=test_ctx.user_factory(
                    name='u1', rank=db.User.RANK_ADMINISTRATOR)))

@pytest.mark.parametrize('field', ['name', 'password'])
def test_trying_to_omit_mandatory_field(test_ctx, field):
    input = {
        'name': 'chewie',
        'email': 'asd@asd.asd',
        'password': 'oks',
    }
    del input[field]
    with pytest.raises(errors.ValidationError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=input,
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

@pytest.mark.parametrize('field', ['rank', 'email', 'avatarStyle'])
def test_omitting_optional_field(test_ctx, tmpdir, field):
    config.config['data_dir'] = str(tmpdir.mkdir('data'))
    config.config['data_url'] = 'http://example.com/data/'
    input = {
        'name': 'chewie',
        'email': 'asd@asd.asd',
        'password': 'oks',
        'rank': 'moderator',
        'avatarStyle': 'manual',
    }
    del input[field]
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input=input,
            files={'avatar': EMPTY_PIXEL},
            user=test_ctx.user_factory(rank=db.User.RANK_MODERATOR)))
    assert result is not None

def test_mods_trying_to_become_admin(test_ctx):
    user1 = test_ctx.user_factory(name='u1', rank=db.User.RANK_MODERATOR)
    user2 = test_ctx.user_factory(name='u2', rank=db.User.RANK_MODERATOR)
    db.session.add_all([user1, user2])
    context = test_ctx.context_factory(input={
            'name': 'chewie',
            'email': 'asd@asd.asd',
            'password': 'oks',
            'rank': 'administrator',
        }, user=user1)
    with pytest.raises(errors.AuthError):
        test_ctx.api.post(context)

def test_admin_creating_mod_account(test_ctx):
    user = test_ctx.user_factory(rank=db.User.RANK_ADMINISTRATOR)
    db.session.add(user)
    context = test_ctx.context_factory(input={
            'name': 'chewie',
            'email': 'asd@asd.asd',
            'password': 'oks',
            'rank': 'moderator',
        }, user=user)
    result = test_ctx.api.post(context)
    assert result['user']['rank'] == 'moderator'

def test_uploading_avatar(test_ctx, tmpdir):
    config.config['data_dir'] = str(tmpdir.mkdir('data'))
    config.config['data_url'] = 'http://example.com/data/'
    response = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'name': 'chewie',
                'email': 'asd@asd.asd',
                'password': 'oks',
                'avatarStyle': 'manual',
            },
            files={'avatar': EMPTY_PIXEL},
            user=test_ctx.user_factory(rank=db.User.RANK_MODERATOR)))
    user = users.get_user_by_name('chewie')
    assert user.avatar_style == user.AVATAR_MANUAL
    assert response['user']['avatarUrl'] == \
        'http://example.com/data/avatars/chewie.jpg'
