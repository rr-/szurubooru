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
    ret.detail_api = api.PostDetailApi()
    return ret

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
