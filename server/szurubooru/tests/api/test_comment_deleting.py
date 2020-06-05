import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import comments


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "comments:delete:own": model.User.RANK_REGULAR,
                "comments:delete:any": model.User.RANK_MODERATOR,
            },
        }
    )


def test_deleting_own_comment(user_factory, comment_factory, context_factory):
    user = user_factory()
    comment = comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    result = api.comment_api.delete_comment(
        context_factory(params={"version": 1}, user=user),
        {"comment_id": comment.comment_id},
    )
    assert result == {}
    assert db.session.query(model.Comment).count() == 0


def test_deleting_someones_else_comment(
    user_factory, comment_factory, context_factory
):
    user1 = user_factory(rank=model.User.RANK_REGULAR)
    user2 = user_factory(rank=model.User.RANK_MODERATOR)
    comment = comment_factory(user=user1)
    db.session.add(comment)
    db.session.commit()
    api.comment_api.delete_comment(
        context_factory(params={"version": 1}, user=user2),
        {"comment_id": comment.comment_id},
    )
    assert db.session.query(model.Comment).count() == 0


def test_trying_to_delete_someones_else_comment_without_privileges(
    user_factory, comment_factory, context_factory
):
    user1 = user_factory(rank=model.User.RANK_REGULAR)
    user2 = user_factory(rank=model.User.RANK_REGULAR)
    comment = comment_factory(user=user1)
    db.session.add(comment)
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.comment_api.delete_comment(
            context_factory(params={"version": 1}, user=user2),
            {"comment_id": comment.comment_id},
        )
    assert db.session.query(model.Comment).count() == 1


def test_trying_to_delete_non_existing(user_factory, context_factory):
    with pytest.raises(comments.CommentNotFoundError):
        api.comment_api.delete_comment(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"comment_id": 1},
        )
