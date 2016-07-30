import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.func import util, users

@pytest.fixture
def test_ctx(context_factory, config_injector, user_factory):
    config_injector({
        'privileges': {
            'users:list': db.User.RANK_REGULAR,
            'users:view': db.User.RANK_REGULAR,
            'users:edit:any:email': db.User.RANK_MODERATOR,
        },
        'thumbnails': {'avatar_width': 200},
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.list_api = api.UserListApi()
    ret.detail_api = api.UserDetailApi()
    return ret

def test_retrieving_multiple(test_ctx):
    user1 = test_ctx.user_factory(name='u1', rank=db.User.RANK_MODERATOR)
    user2 = test_ctx.user_factory(name='u2', rank=db.User.RANK_MODERATOR)
    db.session.add_all([user1, user2])
    result = test_ctx.list_api.get(
        test_ctx.context_factory(
            input={'query': '', 'page': 1},
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert result['query'] == ''
    assert result['page'] == 1
    assert result['pageSize'] == 100
    assert result['total'] == 2
    assert [u['name'] for u in result['results']] == ['u1', 'u2']

def test_trying_to_retrieve_multiple_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.list_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)))

def test_retrieving_single(test_ctx):
    db.session.add(test_ctx.user_factory(name='u1', rank=db.User.RANK_REGULAR))
    result = test_ctx.detail_api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
        'u1')
    assert result == {
        'name': 'u1',
        'rank': db.User.RANK_REGULAR,
        'creationTime': datetime.datetime(1997, 1, 1),
        'lastLoginTime': None,
        'avatarStyle': 'gravatar',
        'avatarUrl': 'https://gravatar.com/avatar/' +
            '275876e34cf609db118f3d84b799a790?d=retro&s=200',
        'email': False,
        'commentCount': 0,
        'likedPostCount': False,
        'dislikedPostCount': False,
        'favoritePostCount': 0,
        'uploadedPostCount': 0,
    }
    assert result['email'] is False
    assert result['likedPostCount'] is False
    assert result['dislikedPostCount'] is False

def test_trying_to_retrieve_single_non_existing(test_ctx):
    with pytest.raises(users.UserNotFoundError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            '-')

def test_trying_to_retrieve_single_without_privileges(test_ctx):
    db.session.add(test_ctx.user_factory(name='u1', rank=db.User.RANK_REGULAR))
    with pytest.raises(errors.AuthError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)),
            'u1')
