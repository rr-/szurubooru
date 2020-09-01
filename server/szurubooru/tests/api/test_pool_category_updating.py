from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import pool_categories, snapshots


def _update_category_name(category, name):
    category.name = name


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "pool_categories:edit:name": model.User.RANK_REGULAR,
                "pool_categories:edit:color": model.User.RANK_REGULAR,
                "pool_categories:set_default": model.User.RANK_REGULAR,
            },
        }
    )


def test_simple_updating(user_factory, pool_category_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    category = pool_category_factory(name="name", color="black")
    db.session.add(category)
    db.session.flush()
    with patch("szurubooru.func.pool_categories.serialize_category"), patch(
        "szurubooru.func.pool_categories.update_category_name"
    ), patch("szurubooru.func.pool_categories.update_category_color"), patch(
        "szurubooru.func.snapshots.modify"
    ):
        pool_categories.update_category_name.side_effect = (
            _update_category_name
        )
        pool_categories.serialize_category.return_value = "serialized category"
        result = api.pool_category_api.update_pool_category(
            context_factory(
                params={"name": "changed", "color": "white", "version": 1},
                user=auth_user,
            ),
            {"category_name": "name"},
        )
        assert result == "serialized category"
        pool_categories.update_category_name.assert_called_once_with(
            category, "changed"
        )
        pool_categories.update_category_color.assert_called_once_with(
            category, "white"
        )
        snapshots.modify.assert_called_once_with(category, auth_user)


@pytest.mark.parametrize("field", ["name", "color"])
def test_omitting_optional_field(
    user_factory, pool_category_factory, context_factory, field
):
    db.session.add(pool_category_factory(name="name", color="black"))
    db.session.commit()
    params = {
        "name": "changed",
        "color": "white",
    }
    del params[field]
    with patch("szurubooru.func.pool_categories.serialize_category"), patch(
        "szurubooru.func.pool_categories.update_category_name"
    ), patch("szurubooru.func.snapshots._post_to_webhooks"):
        api.pool_category_api.update_pool_category(
            context_factory(
                params={**params, **{"version": 1}},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"category_name": "name"},
        )


def test_trying_to_update_non_existing(user_factory, context_factory):
    with pytest.raises(pool_categories.PoolCategoryNotFoundError):
        api.pool_category_api.update_pool_category(
            context_factory(
                params={"name": ["dummy"]},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"category_name": "bad"},
        )


@pytest.mark.parametrize(
    "params",
    [
        {"name": "whatever"},
        {"color": "whatever"},
    ],
)
def test_trying_to_update_without_privileges(
    user_factory, pool_category_factory, context_factory, params
):
    db.session.add(pool_category_factory(name="dummy"))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.pool_category_api.update_pool_category(
            context_factory(
                params={**params, **{"version": 1}},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"category_name": "dummy"},
        )


def test_set_as_default(user_factory, pool_category_factory, context_factory):
    category = pool_category_factory(name="name", color="black")
    db.session.add(category)
    db.session.commit()
    with patch("szurubooru.func.pool_categories.serialize_category"), patch(
        "szurubooru.func.pool_categories.set_default_category"
    ):
        pool_categories.update_category_name.side_effect = (
            _update_category_name
        )
        pool_categories.serialize_category.return_value = "serialized category"
        result = api.pool_category_api.set_pool_category_as_default(
            context_factory(
                params={
                    "name": "changed",
                    "color": "white",
                    "version": 1,
                },
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"category_name": "name"},
        )
        assert result == "serialized category"
        pool_categories.set_default_category.assert_called_once_with(category)
