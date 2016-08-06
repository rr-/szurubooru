import pytest
from datetime import datetime
from szurubooru import api, db, errors
from szurubooru.func import util, comments

@pytest.fixture
def test_ctx(config_injector, context_factory, user_factory, comment_factory):
    config_injector({
        'privileges': {
            'comments:delete:own': db.User.RANK_REGULAR,
            'comments:delete:any': db.User.RANK_MODERATOR,
        },
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.comment_factory = comment_factory
    ret.api = api.CommentDetailApi()
    return ret

def test_deleting_own_comment(test_ctx):
    user = test_ctx.user_factory()
    comment = test_ctx.comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    result = test_ctx.api.delete(
        test_ctx.context_factory(input={'version': 1}, user=user),
        comment.comment_id)
    assert result == {}
    assert db.session.query(db.Comment).count() == 0

def test_deleting_someones_else_comment(test_ctx):
    user1 = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    user2 = test_ctx.user_factory(rank=db.User.RANK_MODERATOR)
    comment = test_ctx.comment_factory(user=user1)
    db.session.add(comment)
    db.session.commit()
    result = test_ctx.api.delete(
        test_ctx.context_factory(input={'version': 1}, user=user2),
        comment.comment_id)
    assert db.session.query(db.Comment).count() == 0

def test_trying_to_delete_someones_else_comment_without_privileges(test_ctx):
    user1 = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    user2 = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    comment = test_ctx.comment_factory(user=user1)
    db.session.add(comment)
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.delete(
            test_ctx.context_factory(input={'version': 1}, user=user2),
            comment.comment_id)
    assert db.session.query(db.Comment).count() == 1

def test_trying_to_delete_non_existing(test_ctx):
    with pytest.raises(comments.CommentNotFoundError):
        test_ctx.api.delete(
            test_ctx.context_factory(
                input={'version': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            1)
