from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import pools, snapshots


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"pools:merge": model.User.RANK_REGULAR}})


def test_merging(user_factory, pool_factory, context_factory, post_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    source_pool = pool_factory(id=1)
    target_pool = pool_factory(id=2)
    db.session.add_all([source_pool, target_pool])
    db.session.flush()
    assert source_pool.post_count == 0
    assert target_pool.post_count == 0
    post = post_factory(id=1)
    source_pool.posts = [post]
    db.session.add(post)
    db.session.commit()
    assert source_pool.post_count == 1
    assert target_pool.post_count == 0
    with patch("szurubooru.func.pools.serialize_pool"), patch(
        "szurubooru.func.pools.merge_pools"
    ), patch("szurubooru.func.snapshots.merge"):
        api.pool_api.merge_pools(
            context_factory(
                params={
                    "removeVersion": 1,
                    "mergeToVersion": 1,
                    "remove": 1,
                    "mergeTo": 2,
                },
                user=auth_user,
            )
        )
        pools.merge_pools.called_once_with(source_pool, target_pool)
        snapshots.merge.assert_called_once_with(
            source_pool, target_pool, auth_user
        )


@pytest.mark.parametrize(
    "field", ["remove", "mergeTo", "removeVersion", "mergeToVersion"]
)
def test_trying_to_omit_mandatory_field(
    user_factory, pool_factory, context_factory, field
):
    db.session.add_all(
        [
            pool_factory(id=1),
            pool_factory(id=2),
        ]
    )
    db.session.commit()
    params = {
        "removeVersion": 1,
        "mergeToVersion": 1,
        "remove": 1,
        "mergeTo": 2,
    }
    del params[field]
    with pytest.raises(errors.ValidationError):
        api.pool_api.merge_pools(
            context_factory(
                params=params, user=user_factory(rank=model.User.RANK_REGULAR)
            )
        )


def test_trying_to_merge_non_existing(
    user_factory, pool_factory, context_factory
):
    db.session.add(pool_factory(id=1))
    db.session.commit()
    with pytest.raises(pools.PoolNotFoundError):
        api.pool_api.merge_pools(
            context_factory(
                params={"remove": 1, "mergeTo": 9999},
                user=user_factory(rank=model.User.RANK_REGULAR),
            )
        )
    with pytest.raises(pools.PoolNotFoundError):
        api.pool_api.merge_pools(
            context_factory(
                params={"remove": 9999, "mergeTo": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            )
        )


def test_trying_to_merge_without_privileges(
    user_factory, pool_factory, context_factory
):
    db.session.add_all(
        [
            pool_factory(id=1),
            pool_factory(id=2),
        ]
    )
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.pool_api.merge_pools(
            context_factory(
                params={
                    "removeVersion": 1,
                    "mergeToVersion": 1,
                    "remove": 1,
                    "mergeTo": 2,
                },
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            )
        )
