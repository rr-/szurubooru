import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.func import util, comments, scores

@pytest.fixture
def test_ctx(
        tmpdir, config_injector, context_factory, user_factory, comment_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'data_url': 'http://example.com',
        'privileges': {
            'comments:score': db.User.RANK_REGULAR,
            'users:edit:any:email': db.User.RANK_MODERATOR,
        },
        'thumbnails': {'avatar_width': 200},
    })
    db.session.flush()
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.comment_factory = comment_factory
    ret.api = api.CommentScoreApi()
    return ret

def test_simple_rating(test_ctx, fake_datetime):
    user = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    comment = test_ctx.comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 1}, user=user),
            comment.comment_id)
    assert 'comment' in result
    assert 'text' in result['comment']
    comment = db.session.query(db.Comment).one()
    assert db.session.query(db.CommentScore).count() == 1
    assert comment is not None
    assert comment.score == 1

def test_updating_rating(test_ctx, fake_datetime):
    user = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    comment = test_ctx.comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 1}, user=user),
            comment.comment_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': -1}, user=user),
            comment.comment_id)
    comment = db.session.query(db.Comment).one()
    assert db.session.query(db.CommentScore).count() == 1
    assert comment.score == -1

def test_updating_rating_to_zero(test_ctx, fake_datetime):
    user = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    comment = test_ctx.comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 1}, user=user),
            comment.comment_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 0}, user=user),
            comment.comment_id)
    comment = db.session.query(db.Comment).one()
    assert db.session.query(db.CommentScore).count() == 0
    assert comment.score == 0

def test_deleting_rating(test_ctx, fake_datetime):
    user = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    comment = test_ctx.comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 1}, user=user),
            comment.comment_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.delete(
            test_ctx.context_factory(user=user), comment.comment_id)
    comment = db.session.query(db.Comment).one()
    assert db.session.query(db.CommentScore).count() == 0
    assert comment.score == 0

def test_ratings_from_multiple_users(test_ctx, fake_datetime):
    user1 = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    user2 = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    comment = test_ctx.comment_factory()
    db.session.add_all([user1, user2, comment])
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': 1}, user=user1),
            comment.comment_id)
    with fake_datetime('1997-12-02'):
        result = test_ctx.api.put(
            test_ctx.context_factory(input={'score': -1}, user=user2),
            comment.comment_id)
    comment = db.session.query(db.Comment).one()
    assert db.session.query(db.CommentScore).count() == 2
    assert comment.score == 0

@pytest.mark.parametrize('input,expected_exception', [
    ({'score': None}, errors.ValidationError),
    ({'score': ''}, errors.ValidationError),
    ({'score': -2}, scores.InvalidScoreValueError),
    ({'score': 2}, scores.InvalidScoreValueError),
    ({'score': [1]}, errors.ValidationError),
])
def test_trying_to_pass_invalid_input(test_ctx, input, expected_exception):
    user = test_ctx.user_factory()
    comment = test_ctx.comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with pytest.raises(expected_exception):
        test_ctx.api.put(
            test_ctx.context_factory(input=input, user=user),
            comment.comment_id)

def test_trying_to_omit_mandatory_field(test_ctx):
    user = test_ctx.user_factory()
    comment = test_ctx.comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with pytest.raises(errors.ValidationError):
        test_ctx.api.put(
            test_ctx.context_factory(input={}, user=user),
            comment.comment_id)

def test_trying_to_update_non_existing(test_ctx):
    with pytest.raises(comments.CommentNotFoundError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'score': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            5)

def test_trying_to_rate_without_privileges(test_ctx):
    comment = test_ctx.comment_factory()
    db.session.add(comment)
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'score': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)),
            comment.comment_id)
