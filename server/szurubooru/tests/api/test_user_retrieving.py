import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.util import misc, users

@pytest.fixture
def test_ctx(context_factory, config_injector, user_factory):
    config_injector({
        'privileges': {
            'users:list': 'regular_user',
            'users:view': 'regular_user',
        },
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {'regular_user': 'Peasant'},
    })
    ret = misc.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.list_api = api.UserListApi()
    ret.detail_api = api.UserDetailApi()
    return ret

def test_retrieving_multiple(test_ctx):
    user1 = test_ctx.user_factory(name='u1', rank='mod')
    user2 = test_ctx.user_factory(name='u2', rank='mod')
    db.session.add_all([user1, user2])
    result = test_ctx.list_api.get(
        test_ctx.context_factory(
            input={'query': '', 'page': 1},
            user=test_ctx.user_factory(rank='regular_user')))
    assert result['query'] == ''
    assert result['page'] == 1
    assert result['pageSize'] == 100
    assert result['total'] == 2
    assert [u['name'] for u in result['users']] == ['u1', 'u2']

def test_trying_to_retrieve_multiple_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.list_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank='anonymous')))

def test_retrieving_single(test_ctx):
    db.session.add(test_ctx.user_factory(name='u1', rank='regular_user'))
    result = test_ctx.detail_api.get(
        test_ctx.context_factory(
            input={'query': '', 'page': 1},
            user=test_ctx.user_factory(rank='regular_user')),
        'u1')
    assert result == {
        'user': {
            'name': 'u1',
            'rank': 'regular_user',
            'rankName': 'Peasant',
            'creationTime': datetime.datetime(1997, 1, 1),
            'lastLoginTime': None,
            'avatarStyle': 'gravatar',
            'avatarUrl': 'http://gravatar.com/avatar/' +
                '275876e34cf609db118f3d84b799a790?d=retro&s=200',
        }
    }

def test_trying_to_retrieve_single_non_existing(test_ctx):
    with pytest.raises(users.UserNotFoundError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank='regular_user')),
            '-')

def test_trying_to_retrieve_single_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank='anonymous')),
            '-')
