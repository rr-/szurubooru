from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import comments


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {"privileges": {"comments:score": model.User.RANK_REGULAR}}
    )


def test_simple_rating(
    user_factory, comment_factory, context_factory, fake_datetime
):
    user = user_factory(rank=model.User.RANK_REGULAR)
    comment = comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with patch("szurubooru.func.comments.serialize_comment"), fake_datetime(
        "1997-12-01"
    ):
        comments.serialize_comment.return_value = "serialized comment"
        result = api.comment_api.set_comment_score(
            context_factory(params={"score": 1}, user=user),
            {"comment_id": comment.comment_id},
        )
        assert result == "serialized comment"
        assert db.session.query(model.CommentScore).count() == 1
        assert comment is not None
        assert comment.score == 1


def test_updating_rating(
    user_factory, comment_factory, context_factory, fake_datetime
):
    user = user_factory(rank=model.User.RANK_REGULAR)
    comment = comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with patch("szurubooru.func.comments.serialize_comment"):
        with fake_datetime("1997-12-01"):
            api.comment_api.set_comment_score(
                context_factory(params={"score": 1}, user=user),
                {"comment_id": comment.comment_id},
            )
        with fake_datetime("1997-12-02"):
            api.comment_api.set_comment_score(
                context_factory(params={"score": -1}, user=user),
                {"comment_id": comment.comment_id},
            )
        comment = db.session.query(model.Comment).one()
        assert db.session.query(model.CommentScore).count() == 1
        assert comment.score == -1


def test_updating_rating_to_zero(
    user_factory, comment_factory, context_factory, fake_datetime
):
    user = user_factory(rank=model.User.RANK_REGULAR)
    comment = comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with patch("szurubooru.func.comments.serialize_comment"):
        with fake_datetime("1997-12-01"):
            api.comment_api.set_comment_score(
                context_factory(params={"score": 1}, user=user),
                {"comment_id": comment.comment_id},
            )
        with fake_datetime("1997-12-02"):
            api.comment_api.set_comment_score(
                context_factory(params={"score": 0}, user=user),
                {"comment_id": comment.comment_id},
            )
        comment = db.session.query(model.Comment).one()
        assert db.session.query(model.CommentScore).count() == 0
        assert comment.score == 0


def test_deleting_rating(
    user_factory, comment_factory, context_factory, fake_datetime
):
    user = user_factory(rank=model.User.RANK_REGULAR)
    comment = comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with patch("szurubooru.func.comments.serialize_comment"):
        with fake_datetime("1997-12-01"):
            api.comment_api.set_comment_score(
                context_factory(params={"score": 1}, user=user),
                {"comment_id": comment.comment_id},
            )
        with fake_datetime("1997-12-02"):
            api.comment_api.delete_comment_score(
                context_factory(user=user), {"comment_id": comment.comment_id}
            )
        comment = db.session.query(model.Comment).one()
        assert db.session.query(model.CommentScore).count() == 0
        assert comment.score == 0


def test_ratings_from_multiple_users(
    user_factory, comment_factory, context_factory, fake_datetime
):
    user1 = user_factory(rank=model.User.RANK_REGULAR)
    user2 = user_factory(rank=model.User.RANK_REGULAR)
    comment = comment_factory()
    db.session.add_all([user1, user2, comment])
    db.session.commit()
    with patch("szurubooru.func.comments.serialize_comment"):
        with fake_datetime("1997-12-01"):
            api.comment_api.set_comment_score(
                context_factory(params={"score": 1}, user=user1),
                {"comment_id": comment.comment_id},
            )
        with fake_datetime("1997-12-02"):
            api.comment_api.set_comment_score(
                context_factory(params={"score": -1}, user=user2),
                {"comment_id": comment.comment_id},
            )
        comment = db.session.query(model.Comment).one()
        assert db.session.query(model.CommentScore).count() == 2
        assert comment.score == 0


def test_trying_to_omit_mandatory_field(
    user_factory, comment_factory, context_factory
):
    user = user_factory()
    comment = comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with pytest.raises(errors.ValidationError):
        api.comment_api.set_comment_score(
            context_factory(params={}, user=user),
            {"comment_id": comment.comment_id},
        )


def test_trying_to_update_non_existing(user_factory, context_factory):
    with pytest.raises(comments.CommentNotFoundError):
        api.comment_api.set_comment_score(
            context_factory(
                params={"score": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"comment_id": 5},
        )


def test_trying_to_rate_without_privileges(
    user_factory, comment_factory, context_factory
):
    comment = comment_factory()
    db.session.add(comment)
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.comment_api.set_comment_score(
            context_factory(
                params={"score": 1},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"comment_id": comment.comment_id},
        )
