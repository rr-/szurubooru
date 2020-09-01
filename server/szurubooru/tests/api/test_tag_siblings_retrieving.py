from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import tags


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"tags:view": model.User.RANK_REGULAR}})


def test_get_tag_siblings(user_factory, tag_factory, context_factory):
    db.session.add(tag_factory(names=["tag"]))
    db.session.flush()
    with patch("szurubooru.func.tags.serialize_tag"), patch(
        "szurubooru.func.tags.get_tag_siblings"
    ):
        tags.serialize_tag.side_effect = (
            lambda tag, *args, **kwargs: "serialized tag %s"
            % tag.names[0].name
        )
        tags.get_tag_siblings.return_value = [
            (tag_factory(names=["sib1"]), 1),
            (tag_factory(names=["sib2"]), 3),
        ]
        result = api.tag_api.get_tag_siblings(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"tag_name": "tag"},
        )
        assert result == {
            "results": [
                {
                    "tag": "serialized tag sib1",
                    "occurrences": 1,
                },
                {
                    "tag": "serialized tag sib2",
                    "occurrences": 3,
                },
            ],
        }


def test_trying_to_retrieve_non_existing(user_factory, context_factory):
    with pytest.raises(tags.TagNotFoundError):
        api.tag_api.get_tag_siblings(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"tag_name": "-"},
        )


def test_trying_to_retrieve_without_privileges(user_factory, context_factory):
    with pytest.raises(errors.AuthError):
        api.tag_api.get_tag_siblings(
            context_factory(user=user_factory(rank=model.User.RANK_ANONYMOUS)),
            {"tag_name": "-"},
        )
