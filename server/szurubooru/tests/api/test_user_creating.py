import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.util import auth, misc, users

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
        'thumbnails': {'avatar_width': 200},
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
            user=test_ctx.user_factory(rank='regular_user')))
    result2 = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'name': 'chewie2',
                'email': 'asd@asd.asd',
                'password': 'sok',
            },
            user=test_ctx.user_factory(rank='regular_user')))
    assert result1['user']['rank'] == 'admin'
    assert result2['user']['rank'] == 'regular_user'
    first_user = get_user(test_ctx.session, 'chewie1')
    other_user = get_user(test_ctx.session, 'chewie2')
    assert first_user.rank == 'admin'
    assert other_user.rank == 'regular_user'

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

@pytest.mark.parametrize('field', ['name', 'email', 'password'])
def test_missing_field(test_ctx, field):
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
        test_ctx.api.post(
            test_ctx.context_factory(input=input, user=user))

# TODO: support avatar and avatarStyle
