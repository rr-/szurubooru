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
            'posts:feature': db.User.RANK_REGULAR,
            'posts:view': db.User.RANK_REGULAR,
        },
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.post_factory = post_factory
    ret.api = api.PostFeatureApi()
    return ret

def test_no_featured_post(test_ctx):
    assert posts.try_get_featured_post() is None
    result = test_ctx.api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert result == {'post': None, 'snapshots': [], 'comments': []}

def test_featuring(test_ctx):
    db.session.add(test_ctx.post_factory(id=1))
    db.session.commit()
    assert not posts.get_post_by_id(1).is_featured
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={'id': 1},
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert posts.try_get_featured_post() is not None
    assert posts.try_get_featured_post().post_id == 1
    assert posts.get_post_by_id(1).is_featured
    assert 'post' in result
    assert 'id' in result['post']
    assert 'snapshots' in result
    assert 'comments' in result
    result = test_ctx.api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert 'post' in result
    assert 'id' in result['post']
    assert 'snapshots' in result
    assert 'comments' in result

def test_trying_to_feature_the_same_post_twice(test_ctx):
    db.session.add(test_ctx.post_factory(id=1))
    db.session.commit()
    test_ctx.api.post(
        test_ctx.context_factory(
            input={'id': 1},
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    with pytest.raises(posts.PostAlreadyFeaturedError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={'id': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

def test_featuring_one_post_after_another(test_ctx, fake_datetime):
    db.session.add(test_ctx.post_factory(id=1))
    db.session.add(test_ctx.post_factory(id=2))
    db.session.commit()
    assert posts.try_get_featured_post() is None
    assert not posts.get_post_by_id(1).is_featured
    assert not posts.get_post_by_id(2).is_featured
    with fake_datetime('1997'):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={'id': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    with fake_datetime('1998'):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={'id': 2},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert posts.try_get_featured_post() is not None
    assert posts.try_get_featured_post().post_id == 2
    assert not posts.get_post_by_id(1).is_featured
    assert posts.get_post_by_id(2).is_featured

def test_trying_to_feature_non_existing(test_ctx):
    with pytest.raises(posts.PostNotFoundError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={'id': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_feature_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={'id': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)))

def test_getting_featured_post_without_privileges_to_view(test_ctx):
    try:
        test_ctx.api.get(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)))
    except:
        pytest.fail()
