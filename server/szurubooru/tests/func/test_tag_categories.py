from unittest.mock import patch

import pytest

from szurubooru import db, model
from szurubooru.func import cache, tag_categories


@pytest.fixture(autouse=True)
def purge_cache():
    cache.purge()


def test_serialize_category_when_empty():
    assert tag_categories.serialize_category(None, None) is None


def test_serialize_category(tag_category_factory, tag_factory):
    category = tag_category_factory(name="name", color="color")
    category.category_id = 1
    category.default = True
    tag1 = tag_factory(category=category)
    tag2 = tag_factory(category=category)
    db.session.add_all([category, tag1, tag2])
    db.session.flush()
    result = tag_categories.serialize_category(category)
    assert result == {
        "name": "name",
        "color": "color",
        "default": True,
        "version": 1,
        "order": 1,
        "usages": 2,
    }


def test_create_category_when_first():
    with patch("szurubooru.func.tag_categories.update_category_name"), patch(
        "szurubooru.func.tag_categories.update_category_color"
    ), patch("szurubooru.func.tag_categories.update_category_order"):
        category = tag_categories.create_category("name", "color", 7)
        assert category.default
        tag_categories.update_category_name.assert_called_once_with(
            category, "name"
        )
        tag_categories.update_category_color.assert_called_once_with(
            category, "color"
        )
        tag_categories.update_category_order.assert_called_once_with(
            category, 7
        )


def test_create_category_when_subsequent(tag_category_factory):
    db.session.add(tag_category_factory())
    db.session.flush()
    with patch("szurubooru.func.tag_categories.update_category_name"), patch(
        "szurubooru.func.tag_categories.update_category_color"
    ), patch("szurubooru.func.tag_categories.update_category_order"):
        category = tag_categories.create_category("name", "color", 7)
        assert not category.default
        tag_categories.update_category_name.assert_called_once_with(
            category, "name"
        )
        tag_categories.update_category_color.assert_called_once_with(
            category, "color"
        )
        tag_categories.update_category_order.assert_called_once_with(
            category, 7
        )


def test_update_category_name_with_empty_string(tag_category_factory):
    category = tag_category_factory()
    with pytest.raises(tag_categories.InvalidTagCategoryNameError):
        tag_categories.update_category_name(category, None)


def test_update_category_name_with_invalid_name(
    config_injector, tag_category_factory
):
    config_injector({"tag_category_name_regex": "^[a-z]+$"})
    category = tag_category_factory()
    with pytest.raises(tag_categories.InvalidTagCategoryNameError):
        tag_categories.update_category_name(category, "0")


def test_update_category_name_with_too_long_string(
    config_injector, tag_category_factory
):
    config_injector({"tag_category_name_regex": "^[a-z]+$"})
    category = tag_category_factory()
    with pytest.raises(tag_categories.InvalidTagCategoryNameError):
        tag_categories.update_category_name(category, "a" * 3000)


def test_update_category_name_reusing_other_name(
    config_injector, tag_category_factory
):
    config_injector({"tag_category_name_regex": ".*"})
    db.session.add(tag_category_factory(name="name"))
    db.session.flush()
    category = tag_category_factory()
    with pytest.raises(tag_categories.TagCategoryAlreadyExistsError):
        tag_categories.update_category_name(category, "name")
    with pytest.raises(tag_categories.TagCategoryAlreadyExistsError):
        tag_categories.update_category_name(category, "NAME")


@pytest.mark.parametrize("name", ["name", "NAME"])
def test_update_category_name_reusing_own_name(
    config_injector, tag_category_factory, name
):
    config_injector({"tag_category_name_regex": ".*"})
    category = tag_category_factory(name="name")
    db.session.add(category)
    db.session.flush()
    tag_categories.update_category_name(category, name)
    assert category.name == name


def test_update_category_color_with_empty_string(tag_category_factory):
    category = tag_category_factory()
    with pytest.raises(tag_categories.InvalidTagCategoryColorError):
        tag_categories.update_category_color(category, None)


def test_update_category_color_with_too_long_string(tag_category_factory):
    category = tag_category_factory()
    with pytest.raises(tag_categories.InvalidTagCategoryColorError):
        tag_categories.update_category_color(category, "a" * 3000)


def test_update_category_color_with_invalid_string(tag_category_factory):
    category = tag_category_factory()
    with pytest.raises(tag_categories.InvalidTagCategoryColorError):
        tag_categories.update_category_color(category, "NOPE")


@pytest.mark.parametrize("attempt", ["#aaaaaa", "#012345", "012345", "red"])
def test_update_category_color(attempt, tag_category_factory):
    category = tag_category_factory()
    tag_categories.update_category_color(category, attempt)
    assert category.color == attempt


def test_try_get_category_by_name(tag_category_factory):
    category = tag_category_factory(name="test")
    db.session.add(category)
    db.session.flush()
    assert tag_categories.try_get_category_by_name("test") == category
    assert tag_categories.try_get_category_by_name("TEST") == category
    assert tag_categories.try_get_category_by_name("-") is None


def test_get_category_by_name(tag_category_factory):
    category = tag_category_factory(name="test")
    db.session.add(category)
    db.session.flush()
    assert tag_categories.get_category_by_name("test") == category
    assert tag_categories.get_category_by_name("TEST") == category
    with pytest.raises(tag_categories.TagCategoryNotFoundError):
        tag_categories.get_category_by_name("-")


def test_get_all_category_names(tag_category_factory):
    category1 = tag_category_factory(name="cat1")
    category2 = tag_category_factory(name="cat2")
    db.session.add_all([category2, category1])
    db.session.flush()
    assert tag_categories.get_all_category_names() == ["cat1", "cat2"]


def test_get_all_categories(tag_category_factory):
    category1 = tag_category_factory(name="cat1")
    category2 = tag_category_factory(name="cat2")
    db.session.add_all([category2, category1])
    db.session.flush()
    assert tag_categories.get_all_categories() == [category1, category2]


def test_try_get_default_category_when_no_default(tag_category_factory):
    category1 = tag_category_factory(default=False)
    category2 = tag_category_factory(default=False)
    db.session.add_all([category1, category2])
    db.session.flush()
    actual_default_category = tag_categories.try_get_default_category()
    assert actual_default_category == category1
    assert actual_default_category != category2


def test_try_get_default_category_when_default(tag_category_factory):
    category1 = tag_category_factory(default=False)
    category2 = tag_category_factory(default=True)
    db.session.add_all([category1, category2])
    db.session.flush()
    actual_default_category = tag_categories.try_get_default_category()
    assert actual_default_category == category2
    assert actual_default_category != category1


def test_get_default_category_name(tag_category_factory):
    category1 = tag_category_factory()
    category2 = tag_category_factory(default=True)
    db.session.add_all([category1, category2])
    db.session.flush()
    assert tag_categories.get_default_category_name() == category2.name
    category2.default = False
    db.session.flush()
    cache.purge()
    assert tag_categories.get_default_category_name() == category1.name
    db.session.query(model.TagCategory).delete()
    cache.purge()
    with pytest.raises(tag_categories.TagCategoryNotFoundError):
        tag_categories.get_default_category_name()


def test_get_default_category_name_caching(tag_category_factory):
    category1 = tag_category_factory()
    category2 = tag_category_factory()
    db.session.add_all([category1, category2])
    db.session.flush()
    tag_categories.get_default_category_name()
    db.session.delete(category1)
    db.session.flush()
    assert tag_categories.get_default_category_name() == category1.name
    cache.purge()
    assert tag_categories.get_default_category_name() == category2.name


def test_get_default_category():
    with patch("szurubooru.func.tag_categories.try_get_default_category"):
        tag_categories.try_get_default_category.return_value = None
        with pytest.raises(tag_categories.TagCategoryNotFoundError):
            tag_categories.get_default_category()
        tag_categories.try_get_default_category.return_value = "mocked"
        assert tag_categories.get_default_category() == "mocked"


def test_set_default_category_with_previous_default(tag_category_factory):
    category1 = tag_category_factory(default=True)
    category2 = tag_category_factory()
    db.session.add_all([category1, category2])
    db.session.flush()
    tag_categories.set_default_category(category2)
    assert not category1.default
    assert category2.default


def test_set_default_category_without_previous_default(tag_category_factory):
    category1 = tag_category_factory()
    category2 = tag_category_factory()
    db.session.add_all([category1, category2])
    db.session.flush()
    tag_categories.set_default_category(category2)
    assert category2.default


def test_delete_category_with_no_other_categories(tag_category_factory):
    category = tag_category_factory()
    db.session.add(category)
    db.session.flush()
    with pytest.raises(tag_categories.TagCategoryIsInUseError):
        tag_categories.delete_category(category)


def test_delete_category_with_usages(tag_category_factory, tag_factory):
    db.session.add(tag_category_factory())
    category = tag_category_factory()
    db.session.add(tag_factory(category=category))
    db.session.flush()
    with pytest.raises(tag_categories.TagCategoryIsInUseError):
        tag_categories.delete_category(category)


def test_delete_category(tag_category_factory):
    db.session.add(tag_category_factory())
    category = tag_category_factory(name="target")
    db.session.add(category)
    db.session.flush()
    tag_categories.delete_category(category)
    db.session.flush()
    assert tag_categories.try_get_category_by_name("target") is None
