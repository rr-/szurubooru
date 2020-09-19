import re
from datetime import datetime
from typing import Any, Callable, Dict, List, Optional, Tuple

import sqlalchemy as sa

from szurubooru import config, db, errors, model, rest
from szurubooru.func import pool_categories, posts, serialization, util


class PoolNotFoundError(errors.NotFoundError):
    pass


class PoolAlreadyExistsError(errors.ValidationError):
    pass


class PoolIsInUseError(errors.ValidationError):
    pass


class InvalidPoolNameError(errors.ValidationError):
    pass


class InvalidPoolDuplicateError(errors.ValidationError):
    pass


class InvalidPoolCategoryError(errors.ValidationError):
    pass


class InvalidPoolDescriptionError(errors.ValidationError):
    pass


class InvalidPoolRelationError(errors.ValidationError):
    pass


class InvalidPoolNonexistentPostError(errors.ValidationError):
    pass


def _verify_name_validity(name: str) -> None:
    if util.value_exceeds_column_size(name, model.PoolName.name):
        raise InvalidPoolNameError("Name is too long.")
    name_regex = config.config["pool_name_regex"]
    if not re.match(name_regex, name):
        raise InvalidPoolNameError("Name must satisfy regex %r." % name_regex)


def _get_names(pool: model.Pool) -> List[str]:
    assert pool
    return [pool_name.name for pool_name in pool.names]


def _lower_list(names: List[str]) -> List[str]:
    return [name.lower() for name in names]


def _check_name_intersection(
    names1: List[str], names2: List[str], case_sensitive: bool
) -> bool:
    if not case_sensitive:
        names1 = _lower_list(names1)
        names2 = _lower_list(names2)
    return len(set(names1).intersection(names2)) > 0


def _duplicates(a: List[int]) -> List[int]:
    seen = set()
    dupes = []
    for x in a:
        if x not in seen:
            seen.add(x)
        else:
            dupes.append(x)
    return dupes


def sort_pools(pools: List[model.Pool]) -> List[model.Pool]:
    default_category_name = pool_categories.get_default_category_name()
    return sorted(
        pools,
        key=lambda pool: (
            default_category_name == pool.category.name,
            pool.category.name,
            pool.names[0].name,
        ),
    )


class PoolSerializer(serialization.BaseSerializer):
    def __init__(self, pool: model.Pool) -> None:
        self.pool = pool

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        return {
            "id": self.serialize_id,
            "names": self.serialize_names,
            "category": self.serialize_category,
            "version": self.serialize_version,
            "description": self.serialize_description,
            "creationTime": self.serialize_creation_time,
            "lastEditTime": self.serialize_last_edit_time,
            "postCount": self.serialize_post_count,
            "posts": self.serialize_posts,
        }

    def serialize_id(self) -> Any:
        return self.pool.pool_id

    def serialize_names(self) -> Any:
        return [pool_name.name for pool_name in self.pool.names]

    def serialize_category(self) -> Any:
        return self.pool.category.name

    def serialize_version(self) -> Any:
        return self.pool.version

    def serialize_description(self) -> Any:
        return self.pool.description

    def serialize_creation_time(self) -> Any:
        return self.pool.creation_time

    def serialize_last_edit_time(self) -> Any:
        return self.pool.last_edit_time

    def serialize_post_count(self) -> Any:
        return self.pool.post_count

    def serialize_posts(self) -> Any:
        return [
            post
            for post in [
                posts.serialize_micro_post(rel, None)
                for rel in self.pool.posts
            ]
        ]


def serialize_pool(
    pool: model.Pool, options: List[str] = []
) -> Optional[rest.Response]:
    if not pool:
        return None
    return PoolSerializer(pool).serialize(options)


def serialize_micro_pool(pool: model.Pool) -> Optional[rest.Response]:
    return serialize_pool(
        pool, options=["id", "names", "category", "description", "postCount"]
    )


def try_get_pool_by_id(pool_id: int) -> Optional[model.Pool]:
    return (
        db.session.query(model.Pool)
        .filter(model.Pool.pool_id == pool_id)
        .one_or_none()
    )


def get_pool_by_id(pool_id: int) -> model.Pool:
    pool = try_get_pool_by_id(pool_id)
    if not pool:
        raise PoolNotFoundError("Pool %r not found." % pool_id)
    return pool


def try_get_pool_by_name(name: str) -> Optional[model.Pool]:
    return (
        db.session.query(model.Pool)
        .join(model.PoolName)
        .filter(sa.func.lower(model.PoolName.name) == name.lower())
        .one_or_none()
    )


def get_pool_by_name(name: str) -> model.Pool:
    pool = try_get_pool_by_name(name)
    if not pool:
        raise PoolNotFoundError("Pool %r not found." % name)
    return pool


def get_pools_by_names(names: List[str]) -> List[model.Pool]:
    names = util.icase_unique(names)
    if len(names) == 0:
        return []
    return (
        db.session.query(model.Pool)
        .join(model.PoolName)
        .filter(
            sa.sql.or_(
                sa.func.lower(model.PoolName.name) == name.lower()
                for name in names
            )
        )
        .all()
    )


def get_or_create_pools_by_names(
    names: List[str],
) -> Tuple[List[model.Pool], List[model.Pool]]:
    names = util.icase_unique(names)
    existing_pools = get_pools_by_names(names)
    new_pools = []
    pool_category_name = pool_categories.get_default_category_name()
    for name in names:
        found = False
        for existing_pool in existing_pools:
            if _check_name_intersection(
                _get_names(existing_pool), [name], False
            ):
                found = True
                break
        if not found:
            new_pool = create_pool(
                names=[name], category_name=pool_category_name, post_ids=[]
            )
            db.session.add(new_pool)
            new_pools.append(new_pool)
    return existing_pools, new_pools


def delete(source_pool: model.Pool) -> None:
    assert source_pool
    db.session.delete(source_pool)


def merge_pools(source_pool: model.Pool, target_pool: model.Pool) -> None:
    assert source_pool
    assert target_pool
    if source_pool.pool_id == target_pool.pool_id:
        raise InvalidPoolRelationError("Cannot merge pool with itself.")

    def merge_pool_posts(source_pool_id: int, target_pool_id: int) -> None:
        alias1 = model.PoolPost
        alias2 = sa.orm.util.aliased(model.PoolPost)
        update_stmt = sa.sql.expression.update(alias1).where(
            alias1.pool_id == source_pool_id
        )
        update_stmt = update_stmt.where(
            ~sa.exists()
            .where(alias1.post_id == alias2.post_id)
            .where(alias2.pool_id == target_pool_id)
        )
        update_stmt = update_stmt.values(pool_id=target_pool_id)
        db.session.execute(update_stmt)

    merge_pool_posts(source_pool.pool_id, target_pool.pool_id)
    delete(source_pool)


def create_pool(
    names: List[str], category_name: str, post_ids: List[int]
) -> model.Pool:
    pool = model.Pool()
    pool.creation_time = datetime.utcnow()
    update_pool_names(pool, names)
    update_pool_category_name(pool, category_name)
    update_pool_posts(pool, post_ids)
    return pool


def update_pool_category_name(pool: model.Pool, category_name: str) -> None:
    assert pool
    pool.category = pool_categories.get_category_by_name(category_name)


def update_pool_names(pool: model.Pool, names: List[str]) -> None:
    # sanitize
    assert pool
    names = util.icase_unique([name for name in names if name])
    if not len(names):
        raise InvalidPoolNameError("At least one name must be specified.")
    for name in names:
        _verify_name_validity(name)

    # check for existing pools
    expr = sa.sql.false()
    for name in names:
        expr = expr | (sa.func.lower(model.PoolName.name) == name.lower())
    if pool.pool_id:
        expr = expr & (model.PoolName.pool_id != pool.pool_id)
    existing_pools = db.session.query(model.PoolName).filter(expr).all()
    if len(existing_pools):
        raise PoolAlreadyExistsError(
            "One of names is already used by another pool."
        )

    # remove unwanted items
    for pool_name in pool.names[:]:
        if not _check_name_intersection([pool_name.name], names, True):
            pool.names.remove(pool_name)
    # add wanted items
    for name in names:
        if not _check_name_intersection(_get_names(pool), [name], True):
            pool.names.append(model.PoolName(name, -1))

    # set alias order to match the request
    for i, name in enumerate(names):
        for pool_name in pool.names:
            if pool_name.name.lower() == name.lower():
                pool_name.order = i


def update_pool_description(pool: model.Pool, description: str) -> None:
    assert pool
    if util.value_exceeds_column_size(description, model.Pool.description):
        raise InvalidPoolDescriptionError("Description is too long.")
    pool.description = description or None


def update_pool_posts(pool: model.Pool, post_ids: List[int]) -> None:
    assert pool
    dupes = _duplicates(post_ids)
    if len(dupes) > 0:
        dupes = ", ".join(list(str(x) for x in dupes))
        raise InvalidPoolDuplicateError("Duplicate post(s) in pool: " + dupes)
    ret = posts.get_posts_by_ids(post_ids)
    if len(post_ids) != len(ret):
        missing = set(post_ids) - set(post.post_id for post in ret)
        missing = ", ".join(list(str(x) for x in missing))
        raise InvalidPoolNonexistentPostError(
            "The following posts do not exist: " + missing
        )
    pool.posts.clear()
    for post in ret:
        pool.posts.append(post)
