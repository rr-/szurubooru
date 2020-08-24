from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import posts, snapshots


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "posts:feature": model.User.RANK_REGULAR,
                "posts:view": model.User.RANK_REGULAR,
                "posts:view:featured": model.User.RANK_REGULAR,
            },
        }
    )


def test_featuring(user_factory, post_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    post = post_factory(id=1)
    db.session.add(post)
    db.session.flush()
    assert not posts.get_post_by_id(1).is_featured
    with patch("szurubooru.func.posts.serialize_post"), patch(
        "szurubooru.func.snapshots.modify"
    ):
        posts.serialize_post.return_value = "serialized post"
        result = api.post_api.set_featured_post(
            context_factory(params={"id": 1}, user=auth_user)
        )
        assert result == "serialized post"
        assert posts.try_get_featured_post() is not None
        assert posts.try_get_featured_post().post_id == 1
        assert posts.get_post_by_id(1).is_featured
        result = api.post_api.get_featured_post(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR))
        )
        assert result == "serialized post"
        snapshots.modify.assert_called_once_with(post, auth_user)


def test_trying_to_omit_required_parameter(user_factory, context_factory):
    with pytest.raises(errors.MissingRequiredParameterError):
        api.post_api.set_featured_post(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR))
        )


def test_trying_to_feature_the_same_post_twice(
    user_factory, post_factory, context_factory
):
    db.session.add(post_factory(id=1))
    db.session.commit()
    with patch("szurubooru.func.posts.serialize_post"), patch(
        "szurubooru.func.snapshots._post_to_webhooks"
    ):
        api.post_api.set_featured_post(
            context_factory(
                params={"id": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            )
        )
        with pytest.raises(posts.PostAlreadyFeaturedError):
            api.post_api.set_featured_post(
                context_factory(
                    params={"id": 1},
                    user=user_factory(rank=model.User.RANK_REGULAR),
                )
            )


def test_featuring_one_post_after_another(
    user_factory, post_factory, context_factory, fake_datetime
):
    db.session.add(post_factory(id=1))
    db.session.add(post_factory(id=2))
    db.session.commit()
    assert posts.try_get_featured_post() is None
    assert not posts.get_post_by_id(1).is_featured
    assert not posts.get_post_by_id(2).is_featured
    with patch("szurubooru.func.posts.serialize_post"), patch(
        "szurubooru.func.snapshots._post_to_webhooks"
    ):
        with fake_datetime("1997"):
            api.post_api.set_featured_post(
                context_factory(
                    params={"id": 1},
                    user=user_factory(rank=model.User.RANK_REGULAR),
                )
            )
        with fake_datetime("1998"):
            api.post_api.set_featured_post(
                context_factory(
                    params={"id": 2},
                    user=user_factory(rank=model.User.RANK_REGULAR),
                )
            )
        assert posts.try_get_featured_post() is not None
        assert posts.try_get_featured_post().post_id == 2
        assert not posts.get_post_by_id(1).is_featured
        assert posts.get_post_by_id(2).is_featured


def test_trying_to_feature_non_existing(user_factory, context_factory):
    with pytest.raises(posts.PostNotFoundError):
        api.post_api.set_featured_post(
            context_factory(
                params={"id": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            )
        )


def test_trying_to_retrieve_without_privileges(user_factory, context_factory):
    with pytest.raises(errors.AuthError):
        api.post_api.get_featured_post(
            context_factory(user=user_factory(rank=model.User.RANK_ANONYMOUS))
        )


def test_trying_to_feature_without_privileges(user_factory, context_factory):
    with pytest.raises(errors.AuthError):
        api.post_api.set_featured_post(
            context_factory(user=user_factory(rank=model.User.RANK_ANONYMOUS))
        )
