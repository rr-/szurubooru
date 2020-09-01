from datetime import datetime
from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import comments, posts


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {"privileges": {"comments:create": model.User.RANK_REGULAR}}
    )


def test_creating_comment(
    user_factory, post_factory, context_factory, fake_datetime
):
    post = post_factory()
    user = user_factory(rank=model.User.RANK_REGULAR)
    db.session.add_all([post, user])
    db.session.flush()
    with patch("szurubooru.func.comments.serialize_comment"), fake_datetime(
        "1997-01-01"
    ):
        comments.serialize_comment.return_value = "serialized comment"
        result = api.comment_api.create_comment(
            context_factory(
                params={"text": "input", "postId": post.post_id}, user=user
            )
        )
        assert result == "serialized comment"
        comment = db.session.query(model.Comment).one()
        assert comment.text == "input"
        assert comment.creation_time == datetime(1997, 1, 1)
        assert comment.last_edit_time is None
        assert comment.user and comment.user.user_id == user.user_id
        assert comment.post and comment.post.post_id == post.post_id


@pytest.mark.parametrize(
    "params",
    [
        {"text": None},
        {"text": ""},
        {"text": [None]},
        {"text": [""]},
    ],
)
def test_trying_to_pass_invalid_params(
    user_factory, post_factory, context_factory, params
):
    post = post_factory()
    user = user_factory(rank=model.User.RANK_REGULAR)
    db.session.add_all([post, user])
    db.session.flush()
    real_params = {"text": "input", "postId": post.post_id}
    for key, value in params.items():
        real_params[key] = value
    with pytest.raises(errors.ValidationError):
        api.comment_api.create_comment(
            context_factory(params=real_params, user=user)
        )


@pytest.mark.parametrize("field", ["text", "postId"])
def test_trying_to_omit_mandatory_field(user_factory, context_factory, field):
    params = {
        "text": "input",
        "postId": 1,
    }
    del params[field]
    with pytest.raises(errors.ValidationError):
        api.comment_api.create_comment(
            context_factory(
                params={}, user=user_factory(rank=model.User.RANK_REGULAR)
            )
        )


def test_trying_to_comment_non_existing(user_factory, context_factory):
    user = user_factory(rank=model.User.RANK_REGULAR)
    db.session.add_all([user])
    db.session.flush()
    with pytest.raises(posts.PostNotFoundError):
        api.comment_api.create_comment(
            context_factory(params={"text": "bad", "postId": 5}, user=user)
        )


def test_trying_to_create_without_privileges(user_factory, context_factory):
    with pytest.raises(errors.AuthError):
        api.comment_api.create_comment(
            context_factory(
                params={}, user=user_factory(rank=model.User.RANK_ANONYMOUS)
            )
        )
