from unittest.mock import patch

import pytest

from szurubooru import api, errors, model
from szurubooru.func import pools, posts, snapshots


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"pools:create": model.User.RANK_REGULAR}})


def test_creating_simple_pools(pool_factory, user_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    pool = pool_factory()
    with patch("szurubooru.func.pools.create_pool"), patch(
        "szurubooru.func.pools.get_or_create_pools_by_names"
    ), patch("szurubooru.func.pools.serialize_pool"), patch(
        "szurubooru.func.snapshots.create"
    ):
        posts.get_posts_by_ids.return_value = ([], [])
        pools.create_pool.return_value = pool
        pools.serialize_pool.return_value = "serialized pool"
        result = api.pool_api.create_pool(
            context_factory(
                params={
                    "names": ["pool1", "pool2"],
                    "category": "default",
                    "description": "desc",
                    "posts": [1, 2],
                },
                user=auth_user,
            )
        )
        assert result == "serialized pool"
        pools.create_pool.assert_called_once_with(
            ["pool1", "pool2"], "default", [1, 2]
        )
        snapshots.create.assert_called_once_with(pool, auth_user)


@pytest.mark.parametrize("field", ["names", "category"])
def test_trying_to_omit_mandatory_field(user_factory, context_factory, field):
    params = {
        "names": ["pool1", "pool2"],
        "category": "default",
        "description": "desc",
        "posts": [],
    }
    del params[field]
    with pytest.raises(errors.ValidationError):
        api.pool_api.create_pool(
            context_factory(
                params=params, user=user_factory(rank=model.User.RANK_REGULAR)
            )
        )


@pytest.mark.parametrize("field", ["description", "posts"])
def test_omitting_optional_field(
    pool_factory, user_factory, context_factory, field
):
    params = {
        "names": ["pool1", "pool2"],
        "category": "default",
        "description": "desc",
        "posts": [],
    }
    del params[field]
    with patch("szurubooru.func.pools.create_pool"), patch(
        "szurubooru.func.pools.serialize_pool"
    ), patch("szurubooru.func.snapshots._post_to_webhooks"):
        pools.create_pool.return_value = pool_factory()
        api.pool_api.create_pool(
            context_factory(
                params=params, user=user_factory(rank=model.User.RANK_REGULAR)
            )
        )


def test_trying_to_create_pool_without_privileges(
    user_factory, context_factory
):
    with pytest.raises(errors.AuthError):
        api.pool_api.create_pool(
            context_factory(
                params={
                    "names": ["pool"],
                    "category": "default",
                    "posts": [],
                },
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            )
        )
