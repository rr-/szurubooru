from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import comments


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "comments:list": model.User.RANK_REGULAR,
                "comments:view": model.User.RANK_REGULAR,
            },
        }
    )


def test_retrieving_multiple(user_factory, comment_factory, context_factory):
    comment1 = comment_factory(text="text 1")
    comment2 = comment_factory(text="text 2")
    db.session.add_all([comment1, comment2])
    db.session.flush()
    with patch("szurubooru.func.comments.serialize_comment"):
        comments.serialize_comment.return_value = "serialized comment"
        result = api.comment_api.get_comments(
            context_factory(
                params={"query": "", "offset": 0},
                user=user_factory(rank=model.User.RANK_REGULAR),
            )
        )
        assert result == {
            "query": "",
            "offset": 0,
            "limit": 100,
            "total": 2,
            "results": ["serialized comment", "serialized comment"],
        }


def test_trying_to_retrieve_multiple_without_privileges(
    user_factory, context_factory
):
    with pytest.raises(errors.AuthError):
        api.comment_api.get_comments(
            context_factory(
                params={"query": "", "offset": 0},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            )
        )


def test_retrieving_single(user_factory, comment_factory, context_factory):
    comment = comment_factory(text="dummy text")
    db.session.add(comment)
    db.session.flush()
    with patch("szurubooru.func.comments.serialize_comment"):
        comments.serialize_comment.return_value = "serialized comment"
        result = api.comment_api.get_comment(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"comment_id": comment.comment_id},
        )
        assert result == "serialized comment"


def test_trying_to_retrieve_single_non_existing(user_factory, context_factory):
    with pytest.raises(comments.CommentNotFoundError):
        api.comment_api.get_comment(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"comment_id": 5},
        )


def test_trying_to_retrieve_single_without_privileges(
    user_factory, context_factory
):
    with pytest.raises(errors.AuthError):
        api.comment_api.get_comment(
            context_factory(user=user_factory(rank=model.User.RANK_ANONYMOUS)),
            {"comment_id": 5},
        )
