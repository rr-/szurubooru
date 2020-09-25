import re
from typing import Any, Callable, Dict, List, Optional

import sqlalchemy as sa

from szurubooru import config, db, errors, model, rest
from szurubooru.func import cache, serialization, util

DEFAULT_CATEGORY_NAME_CACHE_KEY = "default-tag-category"


class TagCategoryNotFoundError(errors.NotFoundError):
    pass


class TagCategoryAlreadyExistsError(errors.ValidationError):
    pass


class TagCategoryIsInUseError(errors.ValidationError):
    pass


class InvalidTagCategoryNameError(errors.ValidationError):
    pass


class InvalidTagCategoryColorError(errors.ValidationError):
    pass


def _verify_name_validity(name: str) -> None:
    name_regex = config.config["tag_category_name_regex"]
    if not re.match(name_regex, name):
        raise InvalidTagCategoryNameError(
            "Name must satisfy regex %r." % name_regex
        )


class TagCategorySerializer(serialization.BaseSerializer):
    def __init__(self, category: model.TagCategory) -> None:
        self.category = category

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        return {
            "name": self.serialize_name,
            "version": self.serialize_version,
            "color": self.serialize_color,
            "usages": self.serialize_usages,
            "default": self.serialize_default,
            "order": self.serialize_order,
        }

    def serialize_name(self) -> Any:
        return self.category.name

    def serialize_version(self) -> Any:
        return self.category.version

    def serialize_color(self) -> Any:
        return self.category.color

    def serialize_usages(self) -> Any:
        return self.category.tag_count

    def serialize_default(self) -> Any:
        return self.category.default

    def serialize_order(self) -> Any:
        return self.category.order


def serialize_category(
    category: Optional[model.TagCategory], options: List[str] = []
) -> Optional[rest.Response]:
    if not category:
        return None
    return TagCategorySerializer(category).serialize(options)


def create_category(name: str, color: str, order: int) -> model.TagCategory:
    category = model.TagCategory()
    update_category_name(category, name)
    update_category_color(category, color)
    update_category_order(category, order)
    if not get_all_categories():
        category.default = True
    return category


def update_category_name(category: model.TagCategory, name: str) -> None:
    assert category
    if not name:
        raise InvalidTagCategoryNameError("Name cannot be empty.")
    expr = sa.func.lower(model.TagCategory.name) == name.lower()
    if category.tag_category_id:
        expr = expr & (
            model.TagCategory.tag_category_id != category.tag_category_id
        )
    already_exists = (
        db.session.query(model.TagCategory).filter(expr).count() > 0
    )
    if already_exists:
        raise TagCategoryAlreadyExistsError(
            "A category with this name already exists."
        )
    if util.value_exceeds_column_size(name, model.TagCategory.name):
        raise InvalidTagCategoryNameError("Name is too long.")
    _verify_name_validity(name)
    category.name = name
    cache.remove(DEFAULT_CATEGORY_NAME_CACHE_KEY)


def update_category_color(category: model.TagCategory, color: str) -> None:
    assert category
    if not color:
        raise InvalidTagCategoryColorError("Color cannot be empty.")
    if not re.match(r"^#?[0-9a-z]+$", color):
        raise InvalidTagCategoryColorError("Invalid color.")
    if util.value_exceeds_column_size(color, model.TagCategory.color):
        raise InvalidTagCategoryColorError("Color is too long.")
    category.color = color


def update_category_order(category: model.TagCategory, order: int) -> None:
    assert category
    category.order = order


def try_get_category_by_name(
    name: str, lock: bool = False
) -> Optional[model.TagCategory]:
    query = db.session.query(model.TagCategory).filter(
        sa.func.lower(model.TagCategory.name) == name.lower()
    )
    if lock:
        query = query.with_for_update()
    return query.one_or_none()


def get_category_by_name(name: str, lock: bool = False) -> model.TagCategory:
    category = try_get_category_by_name(name, lock)
    if not category:
        raise TagCategoryNotFoundError("Tag category %r not found." % name)
    return category


def get_all_category_names() -> List[str]:
    return [cat.name for cat in get_all_categories()]


def get_all_categories() -> List[model.TagCategory]:
    return (
        db.session.query(model.TagCategory)
        .order_by(model.TagCategory.order.asc(), model.TagCategory.name.asc())
        .all()
    )


def try_get_default_category(
    lock: bool = False,
) -> Optional[model.TagCategory]:
    query = db.session.query(model.TagCategory).filter(
        model.TagCategory.default
    )
    if lock:
        query = query.with_for_update()
    category = query.first()
    # if for some reason (e.g. as a result of migration) there's no default
    # category, get the first record available.
    if not category:
        query = db.session.query(model.TagCategory).order_by(
            model.TagCategory.tag_category_id.asc()
        )
        if lock:
            query = query.with_for_update()
        category = query.first()
    return category


def get_default_category(lock: bool = False) -> model.TagCategory:
    category = try_get_default_category(lock)
    if not category:
        raise TagCategoryNotFoundError("No tag category created yet.")
    return category


def get_default_category_name() -> str:
    if cache.has(DEFAULT_CATEGORY_NAME_CACHE_KEY):
        return cache.get(DEFAULT_CATEGORY_NAME_CACHE_KEY)
    default_category = get_default_category()
    default_category_name = default_category.name
    cache.put(DEFAULT_CATEGORY_NAME_CACHE_KEY, default_category_name)
    return default_category_name


def set_default_category(category: model.TagCategory) -> None:
    assert category
    old_category = try_get_default_category(lock=True)
    if old_category:
        db.session.refresh(old_category)
        old_category.default = False
    db.session.refresh(category)
    category.default = True
    cache.remove(DEFAULT_CATEGORY_NAME_CACHE_KEY)


def delete_category(category: model.TagCategory) -> None:
    assert category
    if len(get_all_category_names()) == 1:
        raise TagCategoryIsInUseError("Cannot delete the last category.")
    if (category.tag_count or 0) > 0:
        raise TagCategoryIsInUseError(
            "Tag category has some usages and cannot be deleted. "
            + "Please remove this category from relevant tags first.."
        )
    db.session.delete(category)
