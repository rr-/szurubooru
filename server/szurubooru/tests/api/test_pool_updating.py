from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import pools, posts, snapshots


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "pools:create": model.User.RANK_REGULAR,
                "pools:edit:names": model.User.RANK_REGULAR,
                "pools:edit:category": model.User.RANK_REGULAR,
                "pools:edit:description": model.User.RANK_REGULAR,
                "pools:edit:posts": model.User.RANK_REGULAR,
            },
        }
    )


def test_simple_updating(user_factory, pool_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    pool = pool_factory(id=1, names=["pool1", "pool2"])
    db.session.add(pool)
    db.session.commit()
    with patch("szurubooru.func.pools.create_pool"), patch(
        "szurubooru.func.posts.get_posts_by_ids"
    ), patch("szurubooru.func.pools.update_pool_names"), patch(
        "szurubooru.func.pools.update_pool_category_name"
    ), patch(
        "szurubooru.func.pools.update_pool_description"
    ), patch(
        "szurubooru.func.pools.update_pool_posts"
    ), patch(
        "szurubooru.func.pools.serialize_pool"
    ), patch(
        "szurubooru.func.snapshots.modify"
    ):
        posts.get_posts_by_ids.return_value = ([], [])
        pools.serialize_pool.return_value = "serialized pool"
        result = api.pool_api.update_pool(
            context_factory(
                params={
                    "version": 1,
                    "names": ["pool3"],
                    "category": "series",
                    "description": "desc",
                    "posts": [1, 2],
                },
                user=auth_user,
            ),
            {"pool_id": 1},
        )
        assert result == "serialized pool"
        pools.create_pool.assert_not_called()
        pools.update_pool_names.assert_called_once_with(pool, ["pool3"])
        pools.update_pool_category_name.assert_called_once_with(pool, "series")
        pools.update_pool_description.assert_called_once_with(pool, "desc")
        pools.update_pool_posts.assert_called_once_with(pool, [1, 2])
        pools.serialize_pool.assert_called_once_with(pool, options=[])
        snapshots.modify.assert_called_once_with(pool, auth_user)


@pytest.mark.parametrize(
    "field",
    [
        "names",
        "category",
        "description",
        "posts",
    ],
)
def test_omitting_optional_field(
    user_factory, pool_factory, context_factory, field
):
    db.session.add(pool_factory(id=1))
    db.session.commit()
    params = {
        "names": ["pool1", "pool2"],
        "category": "default",
        "description": "desc",
        "posts": [],
    }
    del params[field]
    with patch("szurubooru.func.pools.create_pool"), patch(
        "szurubooru.func.pools.update_pool_names"
    ), patch("szurubooru.func.pools.update_pool_category_name"), patch(
        "szurubooru.func.pools.serialize_pool"
    ):
        api.pool_api.update_pool(
            context_factory(
                params={**params, **{"version": 1}},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"pool_id": 1},
        )


def test_trying_to_update_non_existing(user_factory, context_factory):
    with pytest.raises(pools.PoolNotFoundError):
        api.pool_api.update_pool(
            context_factory(
                params={"names": ["dummy"]},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"pool_id": 9999},
        )


@pytest.mark.parametrize(
    "params",
    [
        {"names": ["whatever"]},
        {"category": "whatever"},
        {"posts": [1]},
    ],
)
def test_trying_to_update_without_privileges(
    user_factory, pool_factory, context_factory, params
):
    db.session.add(pool_factory(id=1))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.pool_api.update_pool(
            context_factory(
                params={**params, **{"version": 1}},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"pool_id": 1},
        )


def test_trying_to_create_pools_without_privileges(
    config_injector, context_factory, pool_factory, user_factory
):
    pool = pool_factory(id=1)
    db.session.add(pool)
    db.session.commit()
    config_injector(
        {
            "privileges": {
                "pools:create": model.User.RANK_ADMINISTRATOR,
                "pools:edit:posts": model.User.RANK_REGULAR,
            },
            "delete_source_files": False,
        }
    )
    with patch("szurubooru.func.posts.get_posts_by_ids"):
        posts.get_posts_by_ids.return_value = ([], ["new-post"])
        with pytest.raises(errors.AuthError):
            api.pool_api.create_pool(
                context_factory(
                    params={"posts": [1, 2], "version": 1},
                    user=user_factory(rank=model.User.RANK_REGULAR),
                ),
                {"pool_id": 1},
            )
