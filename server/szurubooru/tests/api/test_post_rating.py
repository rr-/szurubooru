import pytest
from szurubooru import api, db, errors
from szurubooru.func import util, posts, scores

@pytest.fixture
def test_ctx(
        tmpdir, config_injector, context_factory, user_factory, post_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'data_url': 'http://example.com',
        'ranks': ['anonymous', 'regular_user'],
        'rank_names': {'anonymous': 'Peasant', 'regular_user': 'Lord'},
        'privileges': {'posts:score': 'regular_user'},
        'thumbnails': {'avatar_width': 200},
    })
    db.session.flush()
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.post_factory = post_factory
    ret.api = api.PostScoreApi()
    return ret

def test_simple_rating(test_ctx, fake_datetime):
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(
                input={'score': 1}, user=test_ctx.user_factory()),
            post.post_id)
    assert 'post' in result
    assert 'id' in result['post']
    post = db.session.query(db.Post).one()
    assert db.session.query(db.PostScore).count() == 1
    assert post is not None
    assert post.score == 1

def test_updating_rating(test_ctx, fake_datetime):
    user = test_ctx.user_factory()
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 1}, user=user),
            post.post_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': -1}, user=user),
            post.post_id)
    post = db.session.query(db.Post).one()
    assert db.session.query(db.PostScore).count() == 1
    assert post.score == -1

def test_updating_rating_to_zero(test_ctx, fake_datetime):
    user = test_ctx.user_factory()
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 1}, user=user),
            post.post_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 0}, user=user),
            post.post_id)
    post = db.session.query(db.Post).one()
    assert db.session.query(db.PostScore).count() == 0
    assert post.score == 0

def test_deleting_rating(test_ctx, fake_datetime):
    user = test_ctx.user_factory()
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 1}, user=user),
            post.post_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.delete(
            test_ctx.context_factory(user=user), post.post_id)
    post = db.session.query(db.Post).one()
    assert db.session.query(db.PostScore).count() == 0
    assert post.score == 0

def test_ratings_from_multiple_users(test_ctx, fake_datetime):
    user1 = test_ctx.user_factory()
    user2 = test_ctx.user_factory()
    post = test_ctx.post_factory()
    db.session.add_all([user1, user2, post])
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 1}, user=user1),
            post.post_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': -1}, user=user2),
            post.post_id)
    post = db.session.query(db.Post).one()
    assert db.session.query(db.PostScore).count() == 2
    assert post.score == 0

@pytest.mark.parametrize('input,expected_exception', [
    ({'score': None}, errors.ValidationError),
    ({'score': ''}, errors.ValidationError),
    ({'score': -2}, scores.InvalidScoreError),
    ({'score': 2}, scores.InvalidScoreError),
    ({'score': [1]}, errors.ValidationError),
])
def test_trying_to_pass_invalid_input(test_ctx, input, expected_exception):
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with pytest.raises(expected_exception):
        test_ctx.api.put(
            test_ctx.context_factory(input=input, user=test_ctx.user_factory()),
            post.post_id)

def test_trying_to_omit_mandatory_field(test_ctx):
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with pytest.raises(errors.ValidationError):
        test_ctx.api.put(
            test_ctx.context_factory(input={}, user=test_ctx.user_factory()),
            post.post_id)

def test_trying_to_update_non_existing(test_ctx):
    with pytest.raises(posts.PostNotFoundError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'score': 1},
                user=test_ctx.user_factory()),
            5)

def test_trying_to_rate_without_privileges(test_ctx):
    post = test_ctx.post_factory()
    db.session.add(post)
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'score': 1},
                user=test_ctx.user_factory(rank='anonymous')),
            post.post_id)
