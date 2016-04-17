import datetime
import pytest
from szurubooru import api, config, db, errors
from szurubooru.util import auth, misc, users

EMPTY_PIXEL = \
    b'\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x01\x00\x00\x00\x00' \
    b'\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x01\x00\x2c\x00\x00\x00\x00' \
    b'\x01\x00\x01\x00\x00\x02\x02\x4c\x01\x00\x3b'

def get_user(session, name):
    return session.query(db.User).filter_by(name=name).first()

@pytest.fixture
def test_ctx(
        session, config_injector, context_factory, user_factory):
    config_injector({
        'secret': '',
        'user_name_regex': '.{3,}',
        'password_regex': '.{3,}',
        'default_rank': 'regular_user',
        'thumbnails': {'avatar_width': 200, 'avatar_height': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {},
        'privileges': {'users:create': 'anonymous'},
    })
    ret = misc.dotdict()
    ret.session = session
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.api = api.UserListApi()
    return ret

def test_creating_user(test_ctx, fake_datetime):
    fake_datetime(datetime.datetime(1969, 2, 12))
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'name': 'chewie1',
                'email': 'asd@asd.asd',
                'password': 'oks',
            },
            user=test_ctx.user_factory(rank='regular_user')))
    assert result == {
        'user': {
            'avatarStyle': 'gravatar',
            'avatarUrl': 'http://gravatar.com/avatar/' +
                '6f370c8c7109534c3d5c394123a477d7?d=retro&s=200',
            'creationTime': datetime.datetime(1969, 2, 12),
            'lastLoginTime': None,
            'name': 'chewie1',
            'rank': 'admin',
            'rankName': 'Unknown',
        }
    }
    user = get_user(test_ctx.session, 'chewie1')
    assert user.name == 'chewie1'
    assert user.email == 'asd@asd.asd'
    assert user.rank == 'admin'
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
    assert result1['user']['rank'] == 'admin'
    assert result2['user']['rank'] == 'regular_user'
    first_user = get_user(test_ctx.session, 'chewie1')
    other_user = get_user(test_ctx.session, 'chewie2')
    assert first_user.rank == 'admin'
    assert other_user.rank == 'regular_user'

def test_first_user_does_not_become_admin_if_they_dont_wish_so(test_ctx):
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'name': 'chewie1',
                'email': 'asd@asd.asd',
                'password': 'oks',
                'rank': 'regular_user',
            },
            user=test_ctx.user_factory(rank='anonymous')))
    assert result['user']['rank'] == 'regular_user'

def test_creating_user_that_already_exists(test_ctx):
    test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'name': 'chewie',
                'email': 'asd@asd.asd',
                'password': 'oks',
            },
            user=test_ctx.user_factory(rank='regular_user')))
    with pytest.raises(users.UserAlreadyExistsError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'name': 'chewie',
                    'email': 'asd@asd.asd',
                    'password': 'oks',
                },
                user=test_ctx.user_factory(rank='regular_user')))
    with pytest.raises(users.UserAlreadyExistsError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'name': 'CHEWIE',
                    'email': 'asd@asd.asd',
                    'password': 'oks',
                },
                user=test_ctx.user_factory(rank='regular_user')))

@pytest.mark.parametrize('field', ['name', 'password'])
def test_missing_mandatory_field(test_ctx, field):
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
                user=test_ctx.user_factory(rank='regular_user')))

@pytest.mark.parametrize('field', ['rank', 'email', 'avatarStyle'])
def test_missing_optional_field(test_ctx, tmpdir, field):
    config.config['data_dir'] = str(tmpdir.mkdir('data'))
    config.config['data_url'] = 'http://example.com/data/'
    input = {
        'name': 'chewie',
        'email': 'asd@asd.asd',
        'password': 'oks',
        'rank': 'mod',
        'avatarStyle': 'manual',
    }
    del input[field]
    test_ctx.api.post(
        test_ctx.context_factory(
            input=input,
            files={'avatar': EMPTY_PIXEL},
            user=test_ctx.user_factory(rank='mod')))

@pytest.mark.parametrize('input', [
    {'name': '.'},
    {'name': 'x' * 51},
    {'password': '.'},
    {'rank': '.'},
    {'email': '.'},
    {'email': 'x' * 65},
    {'avatarStyle': 'manual'},
])
def test_invalid_inputs(test_ctx, input):
    user = test_ctx.user_factory(name='u1', rank='admin')
    test_ctx.session.add(user)
    with pytest.raises(errors.ValidationError):
        real_input={
            'name': 'chewie',
            'email': 'asd@asd.asd',
            'password': 'oks',
        }
        for key, value in input.items():
            real_input[key] = value
        test_ctx.api.post(
            test_ctx.context_factory(input=real_input, user=user))

def test_mods_trying_to_become_admin(test_ctx):
    user1 = test_ctx.user_factory(name='u1', rank='mod')
    user2 = test_ctx.user_factory(name='u2', rank='mod')
    test_ctx.session.add_all([user1, user2])
    context = test_ctx.context_factory(input={
            'name': 'chewie',
            'email': 'asd@asd.asd',
            'password': 'oks',
            'rank': 'admin',
        }, user=user1)
    with pytest.raises(errors.AuthError):
        test_ctx.api.post(context)

def test_admin_creating_mod_account(test_ctx):
    user = test_ctx.user_factory(rank='admin')
    test_ctx.session.add(user)
    context = test_ctx.context_factory(input={
            'name': 'chewie',
            'email': 'asd@asd.asd',
            'password': 'oks',
            'rank': 'mod',
        }, user=user)
    result = test_ctx.api.post(context)
    assert result['user']['rank'] == 'mod'

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
            user=test_ctx.user_factory(rank='mod')))
    user = get_user(test_ctx.session, 'chewie')
    assert user.avatar_style == user.AVATAR_MANUAL
    assert response['user']['avatarUrl'] == \
        'http://example.com/data/avatars/chewie.jpg'
