import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.func import util, posts

@pytest.fixture
def test_ctx(
        tmpdir, context_factory, config_injector, user_factory, post_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'data_url': 'http://example.com',
        'privileges': {
            'posts:list': 'regular_user',
            'posts:view': 'regular_user',
        },
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {'regular_user': 'Peasant'},
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.post_factory = post_factory
    ret.list_api = api.PostListApi()
    ret.detail_api = api.PostDetailApi()
    return ret

def test_retrieving_multiple(test_ctx):
    post1 = test_ctx.post_factory(id=1)
    post2 = test_ctx.post_factory(id=2)
    db.session.add_all([post1, post2])
    result = test_ctx.list_api.get(
        test_ctx.context_factory(
            input={'query': '', 'page': 1},
            user=test_ctx.user_factory(rank='regular_user')))
    assert result['query'] == ''
    assert result['page'] == 1
    assert result['pageSize'] == 100
    assert result['total'] == 2
    assert [t['id'] for t in result['results']] == [1, 2]

def test_using_special_tokens(
        test_ctx, config_injector):
    auth_user = test_ctx.user_factory(rank='regular_user')
    post1 = test_ctx.post_factory(id=1)
    post2 = test_ctx.post_factory(id=2)
    post1.favorited_by = [db.PostFavorite(
        user=auth_user, time=datetime.datetime.now())]
    db.session.add_all([post1, post2, auth_user])
    db.session.flush()
    result = test_ctx.list_api.get(
        test_ctx.context_factory(
            input={'query': 'special:fav', 'page': 1},
            user=auth_user))
    assert result['query'] == 'special:fav'
    assert result['page'] == 1
    assert result['pageSize'] == 100
    assert result['total'] == 1
    assert [t['id'] for t in result['results']] == [1]

def test_trying_to_use_special_tokens_without_logging_in(
        test_ctx, config_injector):
    config_injector({
        'privileges': {'posts:list': 'anonymous'},
        'ranks': ['anonymous'],
    })
    with pytest.raises(errors.SearchError):
        test_ctx.list_api.get(
            test_ctx.context_factory(
                input={'query': 'special:fav', 'page': 1},
                user=test_ctx.user_factory(rank='anonymous')))

def test_trying_to_retrieve_multiple_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.list_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank='anonymous')))

def test_retrieving_single(test_ctx):
    db.session.add(test_ctx.post_factory(id=1))
    result = test_ctx.detail_api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank='regular_user')), 1)
    assert 'post' in result
    assert 'id' in result['post']
    assert 'snapshots' in result
    assert 'comments' in result

def test_trying_to_retrieve_single_non_existing(test_ctx):
    with pytest.raises(posts.PostNotFoundError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank='regular_user')),
            '-')

def test_trying_to_retrieve_single_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank='anonymous')),
            '-')
