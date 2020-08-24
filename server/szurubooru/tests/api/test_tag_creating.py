from unittest.mock import patch

import pytest

from szurubooru import api, errors, model
from szurubooru.func import snapshots, tags


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"tags:create": model.User.RANK_REGULAR}})


def test_creating_simple_tags(tag_factory, user_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    tag = tag_factory()
    with patch("szurubooru.func.tags.create_tag"), patch(
        "szurubooru.func.tags.get_or_create_tags_by_names"
    ), patch("szurubooru.func.tags.serialize_tag"), patch(
        "szurubooru.func.snapshots.create"
    ):
        tags.get_or_create_tags_by_names.return_value = ([], [])
        tags.create_tag.return_value = tag
        tags.serialize_tag.return_value = "serialized tag"
        result = api.tag_api.create_tag(
            context_factory(
                params={
                    "names": ["tag1", "tag2"],
                    "category": "meta",
                    "description": "desc",
                    "suggestions": ["sug1", "sug2"],
                    "implications": ["imp1", "imp2"],
                },
                user=auth_user,
            )
        )
        assert result == "serialized tag"
        tags.create_tag.assert_called_once_with(
            ["tag1", "tag2"], "meta", ["sug1", "sug2"], ["imp1", "imp2"]
        )
        snapshots.create.assert_called_once_with(tag, auth_user)


@pytest.mark.parametrize("field", ["names", "category"])
def test_trying_to_omit_mandatory_field(user_factory, context_factory, field):
    params = {
        "names": ["tag1", "tag2"],
        "category": "meta",
        "suggestions": [],
        "implications": [],
    }
    del params[field]
    with pytest.raises(errors.ValidationError):
        api.tag_api.create_tag(
            context_factory(
                params=params, user=user_factory(rank=model.User.RANK_REGULAR)
            )
        )


@pytest.mark.parametrize("field", ["implications", "suggestions"])
def test_omitting_optional_field(
    tag_factory, user_factory, context_factory, field
):
    params = {
        "names": ["tag1", "tag2"],
        "category": "meta",
        "suggestions": [],
        "implications": [],
    }
    del params[field]
    with patch("szurubooru.func.tags.create_tag"), patch(
        "szurubooru.func.tags.serialize_tag"
    ), patch("szurubooru.func.snapshots._post_to_webhooks"):
        tags.create_tag.return_value = tag_factory()
        api.tag_api.create_tag(
            context_factory(
                params=params, user=user_factory(rank=model.User.RANK_REGULAR)
            )
        )


def test_trying_to_create_tag_without_privileges(
    user_factory, context_factory
):
    with pytest.raises(errors.AuthError):
        api.tag_api.create_tag(
            context_factory(
                params={
                    "names": ["tag"],
                    "category": "meta",
                    "suggestions": ["tag"],
                    "implications": [],
                },
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            )
        )
