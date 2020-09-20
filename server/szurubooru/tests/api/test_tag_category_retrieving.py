import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import tag_categories


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "tag_categories:list": model.User.RANK_REGULAR,
                "tag_categories:view": model.User.RANK_REGULAR,
            },
        }
    )


def test_retrieving_multiple(
    user_factory, tag_category_factory, context_factory
):
    db.session.add_all(
        [
            tag_category_factory(name="c1"),
            tag_category_factory(name="c2"),
        ]
    )
    db.session.flush()
    result = api.tag_category_api.get_tag_categories(
        context_factory(user=user_factory(rank=model.User.RANK_REGULAR))
    )
    assert [cat["name"] for cat in result["results"]] == ["c1", "c2"]


def test_retrieving_single(
    user_factory, tag_category_factory, context_factory
):
    db.session.add(tag_category_factory(name="cat"))
    db.session.flush()
    result = api.tag_category_api.get_tag_category(
        context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
        {"category_name": "cat"},
    )
    assert result == {
        "name": "cat",
        "color": "dummy",
        "usages": 0,
        "default": False,
        "order": 1,
        "version": 1,
    }


def test_trying_to_retrieve_single_non_existing(user_factory, context_factory):
    with pytest.raises(tag_categories.TagCategoryNotFoundError):
        api.tag_category_api.get_tag_category(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"category_name": "-"},
        )


def test_trying_to_retrieve_single_without_privileges(
    user_factory, context_factory
):
    with pytest.raises(errors.AuthError):
        api.tag_category_api.get_tag_category(
            context_factory(user=user_factory(rank=model.User.RANK_ANONYMOUS)),
            {"category_name": "-"},
        )
