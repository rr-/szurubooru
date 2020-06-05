import re
from typing import Any, Callable, Dict, List, Optional

import sqlalchemy as sa

from szurubooru import config, db, errors, model, rest
from szurubooru.func import cache, serialization, util

DEFAULT_CATEGORY_NAME_CACHE_KEY = "default-pool-category"


class PoolCategoryNotFoundError(errors.NotFoundError):
    pass


class PoolCategoryAlreadyExistsError(errors.ValidationError):
    pass


class PoolCategoryIsInUseError(errors.ValidationError):
    pass


class InvalidPoolCategoryNameError(errors.ValidationError):
    pass


class InvalidPoolCategoryColorError(errors.ValidationError):
    pass


def _verify_name_validity(name: str) -> None:
    name_regex = config.config["pool_category_name_regex"]
    if not re.match(name_regex, name):
        raise InvalidPoolCategoryNameError(
            "Name must satisfy regex %r." % name_regex
        )


class PoolCategorySerializer(serialization.BaseSerializer):
    def __init__(self, category: model.PoolCategory) -> None:
        self.category = category

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        return {
            "name": self.serialize_name,
            "version": self.serialize_version,
            "color": self.serialize_color,
            "usages": self.serialize_usages,
            "default": self.serialize_default,
        }

    def serialize_name(self) -> Any:
        return self.category.name

    def serialize_version(self) -> Any:
        return self.category.version

    def serialize_color(self) -> Any:
        return self.category.color

    def serialize_usages(self) -> Any:
        return self.category.pool_count

    def serialize_default(self) -> Any:
        return self.category.default


def serialize_category(
    category: Optional[model.PoolCategory], options: List[str] = []
) -> Optional[rest.Response]:
    if not category:
        return None
    return PoolCategorySerializer(category).serialize(options)


def create_category(name: str, color: str) -> model.PoolCategory:
    category = model.PoolCategory()
    update_category_name(category, name)
    update_category_color(category, color)
    if not get_all_categories():
        category.default = True
    return category


def update_category_name(category: model.PoolCategory, name: str) -> None:
    assert category
    if not name:
        raise InvalidPoolCategoryNameError("Name cannot be empty.")
    expr = sa.func.lower(model.PoolCategory.name) == name.lower()
    if category.pool_category_id:
        expr = expr & (
            model.PoolCategory.pool_category_id != category.pool_category_id
        )
    already_exists = (
        db.session.query(model.PoolCategory).filter(expr).count() > 0
    )
    if already_exists:
        raise PoolCategoryAlreadyExistsError(
            "A category with this name already exists."
        )
    if util.value_exceeds_column_size(name, model.PoolCategory.name):
        raise InvalidPoolCategoryNameError("Name is too long.")
    _verify_name_validity(name)
    category.name = name
    cache.remove(DEFAULT_CATEGORY_NAME_CACHE_KEY)


def update_category_color(category: model.PoolCategory, color: str) -> None:
    assert category
    if not color:
        raise InvalidPoolCategoryColorError("Color cannot be empty.")
    if not re.match(r"^#?[0-9a-z]+$", color):
        raise InvalidPoolCategoryColorError("Invalid color.")
    if util.value_exceeds_column_size(color, model.PoolCategory.color):
        raise InvalidPoolCategoryColorError("Color is too long.")
    category.color = color


def try_get_category_by_name(
    name: str, lock: bool = False
) -> Optional[model.PoolCategory]:
    query = db.session.query(model.PoolCategory).filter(
        sa.func.lower(model.PoolCategory.name) == name.lower()
    )
    if lock:
        query = query.with_for_update()
    return query.one_or_none()


def get_category_by_name(name: str, lock: bool = False) -> model.PoolCategory:
    category = try_get_category_by_name(name, lock)
    if not category:
        raise PoolCategoryNotFoundError("Pool category %r not found." % name)
    return category


def get_all_category_names() -> List[str]:
    return [cat.name for cat in get_all_categories()]


def get_all_categories() -> List[model.PoolCategory]:
    return (
        db.session.query(model.PoolCategory)
        .order_by(model.PoolCategory.name.asc())
        .all()
    )


def try_get_default_category(
    lock: bool = False,
) -> Optional[model.PoolCategory]:
    query = db.session.query(model.PoolCategory).filter(
        model.PoolCategory.default
    )
    if lock:
        query = query.with_for_update()
    category = query.first()
    # if for some reason (e.g. as a result of migration) there's no default
    # category, get the first record available.
    if not category:
        query = db.session.query(model.PoolCategory).order_by(
            model.PoolCategory.pool_category_id.asc()
        )
        if lock:
            query = query.with_for_update()
        category = query.first()
    return category


def get_default_category(lock: bool = False) -> model.PoolCategory:
    category = try_get_default_category(lock)
    if not category:
        raise PoolCategoryNotFoundError("No pool category created yet.")
    return category


def get_default_category_name() -> str:
    if cache.has(DEFAULT_CATEGORY_NAME_CACHE_KEY):
        return cache.get(DEFAULT_CATEGORY_NAME_CACHE_KEY)
    default_category = get_default_category()
    default_category_name = default_category.name
    cache.put(DEFAULT_CATEGORY_NAME_CACHE_KEY, default_category_name)
    return default_category_name


def set_default_category(category: model.PoolCategory) -> None:
    assert category
    old_category = try_get_default_category(lock=True)
    if old_category:
        db.session.refresh(old_category)
        old_category.default = False
    db.session.refresh(category)
    category.default = True
    cache.remove(DEFAULT_CATEGORY_NAME_CACHE_KEY)


def delete_category(category: model.PoolCategory) -> None:
    assert category
    if len(get_all_category_names()) == 1:
        raise PoolCategoryIsInUseError("Cannot delete the last category.")
    if (category.pool_count or 0) > 0:
        raise PoolCategoryIsInUseError(
            "Pool category has some usages and cannot be deleted. "
            + "Please remove this category from relevant pools first."
        )
    db.session.delete(category)
