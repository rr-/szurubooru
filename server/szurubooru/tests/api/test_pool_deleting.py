from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import pools, snapshots


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"pools:delete": model.User.RANK_REGULAR}})


def test_deleting(user_factory, pool_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    pool = pool_factory(id=1)
    db.session.add(pool)
    db.session.commit()
    with patch("szurubooru.func.snapshots.delete"):
        result = api.pool_api.delete_pool(
            context_factory(params={"version": 1}, user=auth_user),
            {"pool_id": 1},
        )
        assert result == {}
        assert db.session.query(model.Pool).count() == 0
        snapshots.delete.assert_called_once_with(pool, auth_user)


def test_deleting_used(
    user_factory, pool_factory, context_factory, post_factory
):
    pool = pool_factory(id=1)
    post = post_factory(id=1)
    pool.posts.append(post)
    db.session.add_all([pool, post])
    db.session.commit()
    with patch("szurubooru.func.snapshots._post_to_webhooks"):
        api.pool_api.delete_pool(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"pool_id": 1},
        )
        db.session.refresh(post)
        assert db.session.query(model.Pool).count() == 0
        assert db.session.query(model.PoolPost).count() == 0
        assert post.pools == []


def test_trying_to_delete_non_existing(user_factory, context_factory):
    with pytest.raises(pools.PoolNotFoundError):
        api.pool_api.delete_pool(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"pool_id": 9999},
        )


def test_trying_to_delete_without_privileges(
    user_factory, pool_factory, context_factory
):
    db.session.add(pool_factory(id=1))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.pool_api.delete_pool(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"pool_id": 1},
        )
    assert db.session.query(model.Pool).count() == 1
