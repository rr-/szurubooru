from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import pool_categories, snapshots


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {"pool_categories:delete": model.User.RANK_REGULAR},
        }
    )


def test_deleting(user_factory, pool_category_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    category = pool_category_factory(name="category")
    db.session.add(pool_category_factory(name="root"))
    db.session.add(category)
    db.session.flush()
    with patch("szurubooru.func.snapshots.delete"):
        result = api.pool_category_api.delete_pool_category(
            context_factory(params={"version": 1}, user=auth_user),
            {"category_name": "category"},
        )
        assert result == {}
        assert db.session.query(model.PoolCategory).count() == 1
        assert db.session.query(model.PoolCategory).one().name == "root"
        snapshots.delete.assert_called_once_with(category, auth_user)


def test_trying_to_delete_used(
    user_factory, pool_category_factory, pool_factory, context_factory
):
    category = pool_category_factory(name="category")
    db.session.add(category)
    db.session.flush()
    pool = pool_factory(names=["pool"], category=category)
    db.session.add(pool)
    db.session.commit()
    with pytest.raises(pool_categories.PoolCategoryIsInUseError):
        api.pool_category_api.delete_pool_category(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"category_name": "category"},
        )
    assert db.session.query(model.PoolCategory).count() == 1


def test_trying_to_delete_last(
    user_factory, pool_category_factory, context_factory
):
    db.session.add(pool_category_factory(name="root"))
    db.session.commit()
    with pytest.raises(pool_categories.PoolCategoryIsInUseError):
        api.pool_category_api.delete_pool_category(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"category_name": "root"},
        )


def test_trying_to_delete_non_existing(user_factory, context_factory):
    with pytest.raises(pool_categories.PoolCategoryNotFoundError):
        api.pool_category_api.delete_pool_category(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"category_name": "bad"},
        )


def test_trying_to_delete_without_privileges(
    user_factory, pool_category_factory, context_factory
):
    db.session.add(pool_category_factory(name="category"))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.pool_category_api.delete_pool_category(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"category_name": "category"},
        )
    assert db.session.query(model.PoolCategory).count() == 1
