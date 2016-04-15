import pytest
from datetime import datetime
from szurubooru import api, db, errors

@pytest.fixture
def user_list_api():
    return api.UserListApi()

@pytest.fixture
def user_detail_api():
    return api.UserDetailApi()

def test_retrieving_multiple(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_list_api):
    config_injector({
        'privileges': {'users:list': 'regular_user'},
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {},
    })
    user1 = user_factory(name='u1', rank='mod')
    user2 = user_factory(name='u2', rank='mod')
    session.add_all([user1, user2])
    result = user_list_api.get(
        context_factory(
            input={'query': '', 'page': 1},
            user=user_factory(rank='regular_user')))
    assert result['query'] == ''
    assert result['page'] == 1
    assert result['pageSize'] == 100
    assert result['total'] == 2
    assert [u['name'] for u in result['users']] == ['u1', 'u2']

def test_retrieving_multiple_without_privileges(
        context_factory, config_injector, user_factory, user_list_api):
    config_injector({
        'privileges': {'users:list': 'regular_user'},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
    })
    with pytest.raises(errors.AuthError):
        user_list_api.get(
            context_factory(
                input={'query': '', 'page': 1},
                user=user_factory(rank='anonymous')))

def test_retrieving_multiple_with_privileges(
        context_factory, config_injector, user_factory, user_list_api):
    config_injector({
        'privileges': {'users:list': 'regular_user'},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
    })
    result = user_list_api.get(
        context_factory(
            input={'query': 'asd', 'page': 1},
            user=user_factory(rank='regular_user')))
    assert result['query'] == 'asd'
    assert result['page'] == 1
    assert result['pageSize'] == 100
    assert result['total'] == 0
    assert [u['name'] for u in result['users']] == []

def test_retrieving_single(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api):
    config_injector({
        'privileges': {'users:view': 'regular_user'},
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {},
    })
    user = user_factory(name='u1', rank='regular_user')
    session.add(user)
    result = user_detail_api.get(
        context_factory(
            input={'query': '', 'page': 1},
            user=user_factory(rank='regular_user')),
        'u1')
    assert result['user']['id'] == user.user_id
    assert result['user']['name'] == 'u1'
    assert result['user']['rank'] == 'regular_user'
    assert result['user']['creationTime'] == datetime(1997, 1, 1)
    assert result['user']['lastLoginTime'] == None
    assert result['user']['avatarStyle'] == 'gravatar'

def test_retrieving_non_existing(
        context_factory, config_injector, user_factory, user_detail_api):
    config_injector({
        'privileges': {'users:view': 'regular_user'},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
    })
    with pytest.raises(errors.NotFoundError):
        user_detail_api.get(
            context_factory(
                input={'query': '', 'page': 1},
                user=user_factory(rank='regular_user')),
            '-')

def test_retrieving_single_without_privileges(
        context_factory, config_injector, user_factory, user_detail_api):
    config_injector({
        'privileges': {'users:view': 'regular_user'},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
    })
    with pytest.raises(errors.AuthError):
        user_detail_api.get(
            context_factory(
                input={'query': '', 'page': 1},
                user=user_factory(rank='anonymous')),
            '-')
