from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import snapshots, tag_categories, tags


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {"tag_categories:delete": model.User.RANK_REGULAR},
        }
    )


def test_deleting(user_factory, tag_category_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    category = tag_category_factory(name="category")
    db.session.add(tag_category_factory(name="root"))
    db.session.add(category)
    db.session.flush()
    with patch("szurubooru.func.snapshots.delete"):
        result = api.tag_category_api.delete_tag_category(
            context_factory(params={"version": 1}, user=auth_user),
            {"category_name": "category"},
        )
        assert result == {}
        assert db.session.query(model.TagCategory).count() == 1
        assert db.session.query(model.TagCategory).one().name == "root"
        snapshots.delete.assert_called_once_with(category, auth_user)


def test_trying_to_delete_used(
    user_factory, tag_category_factory, tag_factory, context_factory
):
    category = tag_category_factory(name="category")
    db.session.add(category)
    db.session.flush()
    tag = tag_factory(names=["tag"], category=category)
    db.session.add(tag)
    db.session.commit()
    with pytest.raises(tag_categories.TagCategoryIsInUseError):
        api.tag_category_api.delete_tag_category(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"category_name": "category"},
        )
    assert db.session.query(model.TagCategory).count() == 1


def test_trying_to_delete_last(
    user_factory, tag_category_factory, context_factory
):
    db.session.add(tag_category_factory(name="root"))
    db.session.commit()
    with pytest.raises(tag_categories.TagCategoryIsInUseError):
        api.tag_category_api.delete_tag_category(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"category_name": "root"},
        )


def test_trying_to_delete_non_existing(user_factory, context_factory):
    with pytest.raises(tag_categories.TagCategoryNotFoundError):
        api.tag_category_api.delete_tag_category(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"category_name": "bad"},
        )


def test_trying_to_delete_without_privileges(
    user_factory, tag_category_factory, context_factory
):
    db.session.add(tag_category_factory(name="category"))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.tag_category_api.delete_tag_category(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"category_name": "category"},
        )
    assert db.session.query(model.TagCategory).count() == 1
