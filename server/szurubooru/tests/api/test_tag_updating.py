from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import snapshots, tags


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "tags:create": model.User.RANK_REGULAR,
                "tags:edit:names": model.User.RANK_REGULAR,
                "tags:edit:category": model.User.RANK_REGULAR,
                "tags:edit:description": model.User.RANK_REGULAR,
                "tags:edit:suggestions": model.User.RANK_REGULAR,
                "tags:edit:implications": model.User.RANK_REGULAR,
            },
        }
    )


def test_simple_updating(user_factory, tag_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    tag = tag_factory(names=["tag1", "tag2"])
    db.session.add(tag)
    db.session.commit()
    with patch("szurubooru.func.tags.create_tag"), patch(
        "szurubooru.func.tags.get_or_create_tags_by_names"
    ), patch("szurubooru.func.tags.update_tag_names"), patch(
        "szurubooru.func.tags.update_tag_category_name"
    ), patch(
        "szurubooru.func.tags.update_tag_description"
    ), patch(
        "szurubooru.func.tags.update_tag_suggestions"
    ), patch(
        "szurubooru.func.tags.update_tag_implications"
    ), patch(
        "szurubooru.func.tags.serialize_tag"
    ), patch(
        "szurubooru.func.snapshots.modify"
    ):
        tags.get_or_create_tags_by_names.return_value = ([], [])
        tags.serialize_tag.return_value = "serialized tag"
        result = api.tag_api.update_tag(
            context_factory(
                params={
                    "version": 1,
                    "names": ["tag3"],
                    "category": "character",
                    "description": "desc",
                    "suggestions": ["sug1", "sug2"],
                    "implications": ["imp1", "imp2"],
                },
                user=auth_user,
            ),
            {"tag_name": "tag1"},
        )
        assert result == "serialized tag"
        tags.create_tag.assert_not_called()
        tags.update_tag_names.assert_called_once_with(tag, ["tag3"])
        tags.update_tag_category_name.assert_called_once_with(tag, "character")
        tags.update_tag_description.assert_called_once_with(tag, "desc")
        tags.update_tag_suggestions.assert_called_once_with(
            tag, ["sug1", "sug2"]
        )
        tags.update_tag_implications.assert_called_once_with(
            tag, ["imp1", "imp2"]
        )
        tags.serialize_tag.assert_called_once_with(tag, options=[])
        snapshots.modify.assert_called_once_with(tag, auth_user)


@pytest.mark.parametrize(
    "field",
    [
        "names",
        "category",
        "description",
        "implications",
        "suggestions",
    ],
)
def test_omitting_optional_field(
    user_factory, tag_factory, context_factory, field
):
    db.session.add(tag_factory(names=["tag"]))
    db.session.commit()
    params = {
        "names": ["tag1", "tag2"],
        "category": "meta",
        "description": "desc",
        "suggestions": [],
        "implications": [],
    }
    del params[field]
    with patch("szurubooru.func.tags.create_tag"), patch(
        "szurubooru.func.tags.update_tag_names"
    ), patch("szurubooru.func.tags.update_tag_category_name"), patch(
        "szurubooru.func.tags.serialize_tag"
    ):
        api.tag_api.update_tag(
            context_factory(
                params={**params, **{"version": 1}},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"tag_name": "tag"},
        )


def test_trying_to_update_non_existing(user_factory, context_factory):
    with pytest.raises(tags.TagNotFoundError):
        api.tag_api.update_tag(
            context_factory(
                params={"names": ["dummy"]},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"tag_name": "tag1"},
        )


@pytest.mark.parametrize(
    "params",
    [
        {"names": "whatever"},
        {"category": "whatever"},
        {"suggestions": ["whatever"]},
        {"implications": ["whatever"]},
    ],
)
def test_trying_to_update_without_privileges(
    user_factory, tag_factory, context_factory, params
):
    db.session.add(tag_factory(names=["tag"]))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.tag_api.update_tag(
            context_factory(
                params={**params, **{"version": 1}},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"tag_name": "tag"},
        )


def test_trying_to_create_tags_without_privileges(
    config_injector, context_factory, tag_factory, user_factory
):
    tag = tag_factory(names=["tag"])
    db.session.add(tag)
    db.session.commit()
    config_injector(
        {
            "privileges": {
                "tags:create": model.User.RANK_ADMINISTRATOR,
                "tags:edit:suggestions": model.User.RANK_REGULAR,
                "tags:edit:implications": model.User.RANK_REGULAR,
            }
        }
    )
    with patch("szurubooru.func.tags.get_or_create_tags_by_names"):
        tags.get_or_create_tags_by_names.return_value = ([], ["new-tag"])
        with pytest.raises(errors.AuthError):
            api.tag_api.update_tag(
                context_factory(
                    params={"suggestions": ["tag1", "tag2"], "version": 1},
                    user=user_factory(rank=model.User.RANK_REGULAR),
                ),
                {"tag_name": "tag"},
            )
        db.session.rollback()
        with pytest.raises(errors.AuthError):
            api.tag_api.update_tag(
                context_factory(
                    params={"implications": ["tag1", "tag2"], "version": 1},
                    user=user_factory(rank=model.User.RANK_REGULAR),
                ),
                {"tag_name": "tag"},
            )
