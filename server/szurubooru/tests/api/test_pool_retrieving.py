from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import pools


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "pools:list": model.User.RANK_REGULAR,
                "pools:view": model.User.RANK_REGULAR,
            },
        }
    )


def test_retrieving_multiple(user_factory, pool_factory, context_factory):
    pool1 = pool_factory(id=1)
    pool2 = pool_factory(id=2)
    db.session.add_all([pool2, pool1])
    db.session.flush()
    with patch("szurubooru.func.pools.serialize_pool"):
        pools.serialize_pool.return_value = "serialized pool"
        result = api.pool_api.get_pools(
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
            "results": ["serialized pool", "serialized pool"],
        }


def test_trying_to_retrieve_multiple_without_privileges(
    user_factory, context_factory
):
    with pytest.raises(errors.AuthError):
        api.pool_api.get_pools(
            context_factory(
                params={"query": "", "offset": 0},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            )
        )


def test_retrieving_single(user_factory, pool_factory, context_factory):
    db.session.add(pool_factory(id=1))
    db.session.flush()
    with patch("szurubooru.func.pools.serialize_pool"):
        pools.serialize_pool.return_value = "serialized pool"
        result = api.pool_api.get_pool(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"pool_id": 1},
        )
        assert result == "serialized pool"


def test_trying_to_retrieve_single_non_existing(user_factory, context_factory):
    with pytest.raises(pools.PoolNotFoundError):
        api.pool_api.get_pool(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"pool_id": 1},
        )


def test_trying_to_retrieve_single_without_privileges(
    user_factory, context_factory
):
    with pytest.raises(errors.AuthError):
        api.pool_api.get_pool(
            context_factory(user=user_factory(rank=model.User.RANK_ANONYMOUS)),
            {"pool_id": 1},
        )
