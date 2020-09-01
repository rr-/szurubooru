from datetime import datetime
from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import posts


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "posts:list": model.User.RANK_REGULAR,
                "posts:view": model.User.RANK_REGULAR,
            },
        }
    )


def test_retrieving_multiple(user_factory, post_factory, context_factory):
    post1 = post_factory(id=1)
    post2 = post_factory(id=2)
    db.session.add_all([post1, post2])
    db.session.flush()
    with patch("szurubooru.func.posts.serialize_post"):
        posts.serialize_post.return_value = "serialized post"
        result = api.post_api.get_posts(
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
            "results": ["serialized post", "serialized post"],
        }


def test_using_special_tokens(user_factory, post_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    post1 = post_factory(id=1)
    post2 = post_factory(id=2)
    post1.favorited_by = [
        model.PostFavorite(user=auth_user, time=datetime.utcnow())
    ]
    db.session.add_all([post1, post2, auth_user])
    db.session.flush()
    with patch("szurubooru.func.posts.serialize_post"):
        posts.serialize_post.side_effect = (
            lambda post, *_args, **_kwargs: "serialized post %d" % post.post_id
        )
        result = api.post_api.get_posts(
            context_factory(
                params={"query": "special:fav", "offset": 0}, user=auth_user
            )
        )
        assert result == {
            "query": "special:fav",
            "offset": 0,
            "limit": 100,
            "total": 1,
            "results": ["serialized post 1"],
        }


def test_trying_to_use_special_tokens_without_logging_in(
    user_factory, context_factory, config_injector
):
    config_injector(
        {
            "privileges": {"posts:list": "anonymous"},
        }
    )
    with pytest.raises(errors.SearchError):
        api.post_api.get_posts(
            context_factory(
                params={"query": "special:fav", "offset": 0},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            )
        )


def test_trying_to_retrieve_multiple_without_privileges(
    user_factory, context_factory
):
    with pytest.raises(errors.AuthError):
        api.post_api.get_posts(
            context_factory(
                params={"query": "", "offset": 0},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            )
        )


def test_retrieving_single(user_factory, post_factory, context_factory):
    db.session.add(post_factory(id=1))
    db.session.flush()
    with patch("szurubooru.func.posts.serialize_post"):
        posts.serialize_post.return_value = "serialized post"
        result = api.post_api.get_post(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"post_id": 1},
        )
        assert result == "serialized post"


def test_trying_to_retrieve_single_non_existing(user_factory, context_factory):
    with pytest.raises(posts.PostNotFoundError):
        api.post_api.get_post(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"post_id": 999},
        )


def test_trying_to_retrieve_single_without_privileges(
    user_factory, context_factory
):
    with pytest.raises(errors.AuthError):
        api.post_api.get_post(
            context_factory(user=user_factory(rank=model.User.RANK_ANONYMOUS)),
            {"post_id": 999},
        )
