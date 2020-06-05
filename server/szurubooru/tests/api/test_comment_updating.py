from datetime import datetime
from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import comments


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "comments:edit:own": model.User.RANK_REGULAR,
                "comments:edit:any": model.User.RANK_MODERATOR,
            },
        }
    )


def test_simple_updating(
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
        result = api.comment_api.update_comment(
            context_factory(
                params={"text": "new text", "version": 1}, user=user
            ),
            {"comment_id": comment.comment_id},
        )
        assert result == "serialized comment"
        assert comment.last_edit_time == datetime(1997, 12, 1)


@pytest.mark.parametrize(
    "params,expected_exception",
    [
        ({"text": None}, comments.EmptyCommentTextError),
        ({"text": ""}, comments.EmptyCommentTextError),
        ({"text": []}, comments.EmptyCommentTextError),
        ({"text": [None]}, errors.ValidationError),
        ({"text": [""]}, comments.EmptyCommentTextError),
    ],
)
def test_trying_to_pass_invalid_params(
    user_factory, comment_factory, context_factory, params, expected_exception
):
    user = user_factory()
    comment = comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with pytest.raises(expected_exception):
        api.comment_api.update_comment(
            context_factory(params={**params, **{"version": 1}}, user=user),
            {"comment_id": comment.comment_id},
        )


def test_trying_to_omit_mandatory_field(
    user_factory, comment_factory, context_factory
):
    user = user_factory()
    comment = comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with pytest.raises(errors.ValidationError):
        api.comment_api.update_comment(
            context_factory(params={"version": 1}, user=user),
            {"comment_id": comment.comment_id},
        )


def test_trying_to_update_non_existing(user_factory, context_factory):
    with pytest.raises(comments.CommentNotFoundError):
        api.comment_api.update_comment(
            context_factory(
                params={"text": "new text"},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"comment_id": 5},
        )


def test_trying_to_update_someones_comment_without_privileges(
    user_factory, comment_factory, context_factory
):
    user = user_factory(rank=model.User.RANK_REGULAR)
    user2 = user_factory(rank=model.User.RANK_REGULAR)
    comment = comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.comment_api.update_comment(
            context_factory(
                params={"text": "new text", "version": 1}, user=user2
            ),
            {"comment_id": comment.comment_id},
        )


def test_updating_someones_comment_with_privileges(
    user_factory, comment_factory, context_factory
):
    user = user_factory(rank=model.User.RANK_REGULAR)
    user2 = user_factory(rank=model.User.RANK_MODERATOR)
    comment = comment_factory(user=user)
    db.session.add(comment)
    db.session.commit()
    with patch("szurubooru.func.comments.serialize_comment"):
        api.comment_api.update_comment(
            context_factory(
                params={"text": "new text", "version": 1}, user=user2
            ),
            {"comment_id": comment.comment_id},
        )
