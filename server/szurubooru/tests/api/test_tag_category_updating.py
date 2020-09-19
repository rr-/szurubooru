from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import snapshots, tag_categories, tags


def _update_category_name(category, name):
    category.name = name


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "tag_categories:edit:name": model.User.RANK_REGULAR,
                "tag_categories:edit:color": model.User.RANK_REGULAR,
                "tag_categories:edit:order": model.User.RANK_REGULAR,
                "tag_categories:set_default": model.User.RANK_REGULAR,
            },
        }
    )


def test_simple_updating(user_factory, tag_category_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    category = tag_category_factory(name="name", color="black")
    db.session.add(category)
    db.session.flush()
    with patch("szurubooru.func.tag_categories.serialize_category"), patch(
        "szurubooru.func.tag_categories.update_category_name"
    ), patch("szurubooru.func.tag_categories.update_category_color"), patch(
        "szurubooru.func.snapshots.modify"
    ):
        tag_categories.update_category_name.side_effect = _update_category_name
        tag_categories.serialize_category.return_value = "serialized category"
        result = api.tag_category_api.update_tag_category(
            context_factory(
                params={"name": "changed", "color": "white", "version": 1},
                user=auth_user,
            ),
            {"category_name": "name"},
        )
        assert result == "serialized category"
        tag_categories.update_category_name.assert_called_once_with(
            category, "changed"
        )
        tag_categories.update_category_color.assert_called_once_with(
            category, "white"
        )
        snapshots.modify.assert_called_once_with(category, auth_user)


@pytest.mark.parametrize("field", ["name", "color"])
def test_omitting_optional_field(
    user_factory, tag_category_factory, context_factory, field
):
    db.session.add(tag_category_factory(name="name", color="black"))
    db.session.commit()
    params = {
        "name": "changed",
        "color": "white",
    }
    del params[field]
    with patch("szurubooru.func.tag_categories.serialize_category"), patch(
        "szurubooru.func.tag_categories.update_category_name"
    ), patch("szurubooru.func.snapshots._post_to_webhooks"):
        api.tag_category_api.update_tag_category(
            context_factory(
                params={**params, **{"version": 1}},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"category_name": "name"},
        )


def test_trying_to_update_non_existing(user_factory, context_factory):
    with pytest.raises(tag_categories.TagCategoryNotFoundError):
        api.tag_category_api.update_tag_category(
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
    user_factory, tag_category_factory, context_factory, params
):
    db.session.add(tag_category_factory(name="dummy"))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.tag_category_api.update_tag_category(
            context_factory(
                params={**params, **{"version": 1}},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"category_name": "dummy"},
        )


def test_set_as_default(user_factory, tag_category_factory, context_factory):
    category = tag_category_factory(name="name", color="black")
    db.session.add(category)
    db.session.commit()
    with patch("szurubooru.func.tag_categories.serialize_category"), patch(
        "szurubooru.func.tag_categories.set_default_category"
    ):
        tag_categories.update_category_name.side_effect = _update_category_name
        tag_categories.serialize_category.return_value = "serialized category"
        result = api.tag_category_api.set_tag_category_as_default(
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
        tag_categories.set_default_category.assert_called_once_with(category)
