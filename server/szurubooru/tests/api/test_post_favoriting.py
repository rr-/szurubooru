import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.func import util, posts

@pytest.fixture
def test_ctx(config_injector, context_factory, user_factory, post_factory):
    config_injector({
        'ranks': ['anonymous', 'regular_user'],
        'rank_names': {'anonymous': 'Peasant', 'regular_user': 'Lord'},
        'privileges': {'posts:favorite': 'regular_user'},
        'thumbnails': {'avatar_width': 200},
    })
    db.session.flush()
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.post_factory = post_factory
    ret.api = api.PostFavoriteApi()
    return ret

def test_simple_rating(test_ctx, fake_datetime):
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(user=test_ctx.user_factory()),
            post.post_id)
    assert 'post' in result
    assert 'id' in result['post']
    post = db.session.query(db.Post).one()
    assert db.session.query(db.PostFavorite).count() == 1
    assert post is not None
    assert post.favorite_count == 1

def test_removing_from_favorites(test_ctx, fake_datetime):
    user = test_ctx.user_factory()
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(user=user),
            post.post_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.delete(
            test_ctx.context_factory(user=user),
            post.post_id)
    post = db.session.query(db.Post).one()
    assert db.session.query(db.PostFavorite).count() == 0
    assert post.favorite_count == 0

def test_favoriting_twice(test_ctx, fake_datetime):
    user = test_ctx.user_factory()
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(user=user),
            post.post_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.post(
            test_ctx.context_factory(user=user),
            post.post_id)
    post = db.session.query(db.Post).one()
    assert db.session.query(db.PostFavorite).count() == 1
    assert post.favorite_count == 1

def test_removing_twice(test_ctx, fake_datetime):
    user = test_ctx.user_factory()
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(user=user),
            post.post_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.delete(
            test_ctx.context_factory(user=user),
            post.post_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.delete(
            test_ctx.context_factory(user=user),
            post.post_id)
    post = db.session.query(db.Post).one()
    assert db.session.query(db.PostFavorite).count() == 0
    assert post.favorite_count == 0

def test_favorites_from_multiple_users(test_ctx, fake_datetime):
    user1 = test_ctx.user_factory()
    user2 = test_ctx.user_factory()
    post = test_ctx.post_factory()
    db.session.add_all([user1, user2, post])
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(user=user1),
            post.post_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.post(
            test_ctx.context_factory(user=user2),
            post.post_id)
    post = db.session.query(db.Post).one()
    assert db.session.query(db.PostFavorite).count() == 2
    assert post.favorite_count == 2
    assert post.last_favorite_time == datetime.datetime(1997, 12, 2)

def test_trying_to_update_non_existing(test_ctx):
    with pytest.raises(posts.PostNotFoundError):
        test_ctx.api.post(
            test_ctx.context_factory(user=test_ctx.user_factory()), 5)

def test_trying_to_rate_without_privileges(test_ctx):
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.post(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank='anonymous')),
            post.post_id)
