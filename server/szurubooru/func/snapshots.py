from datetime import datetime
from typing import Any, Callable, Dict, Optional

import sqlalchemy as sa

from szurubooru import db, model
from szurubooru.func import diff, net, users


def get_tag_category_snapshot(category: model.TagCategory) -> Dict[str, Any]:
    assert category
    return {
        "name": category.name,
        "color": category.color,
        "default": True if category.default else False,
    }


def get_tag_snapshot(tag: model.Tag) -> Dict[str, Any]:
    assert tag
    return {
        "names": [tag_name.name for tag_name in tag.names],
        "category": tag.category.name,
        "suggestions": sorted(rel.first_name for rel in tag.suggestions),
        "implications": sorted(rel.first_name for rel in tag.implications),
    }


def get_pool_category_snapshot(category: model.PoolCategory) -> Dict[str, Any]:
    assert category
    return {
        "name": category.name,
        "color": category.color,
        "default": True if category.default else False,
    }


def get_pool_snapshot(pool: model.Pool) -> Dict[str, Any]:
    assert pool
    return {
        "names": [pool_name.name for pool_name in pool.names],
        "category": pool.category.name,
        "posts": [post.post_id for post in pool.posts],
    }


def get_post_snapshot(post: model.Post) -> Dict[str, Any]:
    assert post
    return {
        "source": post.source,
        "safety": post.safety,
        "checksum": post.checksum,
        "flags": post.flags,
        "featured": post.is_featured,
        "tags": sorted([tag.first_name for tag in post.tags]),
        "relations": sorted([rel.post_id for rel in post.relations]),
        "notes": sorted(
            [
                {
                    "polygon": [
                        [point[0], point[1]] for point in note.polygon
                    ],
                    "text": note.text,
                }
                for note in post.notes
            ],
            key=lambda x: x["polygon"],
        ),
    }


_snapshot_factories = {
    # lambdas allow mocking target functions in the tests
    "tag_category": lambda entity: get_tag_category_snapshot(entity),
    "tag": lambda entity: get_tag_snapshot(entity),
    "post": lambda entity: get_post_snapshot(entity),
    "pool_category": lambda entity: get_pool_category_snapshot(entity),
    "pool": lambda entity: get_pool_snapshot(entity),
}  # type: Dict[model.Base, Callable[[model.Base], Dict[str ,Any]]]


def serialize_snapshot(
    snapshot: model.Snapshot, auth_user: model.User
) -> Dict[str, Any]:
    assert snapshot
    return {
        "operation": snapshot.operation,
        "type": snapshot.resource_type,
        "id": snapshot.resource_name,
        "user": users.serialize_micro_user(snapshot.user, auth_user),
        "data": snapshot.data,
        "time": snapshot.creation_time,
    }


def _post_to_webhooks(snapshot: model.Snapshot) -> None:
    webhook_user = model.User()
    webhook_user.name = None
    webhook_user.rank = "anonymous"
    net.post_to_webhooks(serialize_snapshot(snapshot, webhook_user))


def _create(
    operation: str, entity: model.Base, auth_user: Optional[model.User]
) -> model.Snapshot:
    resource_type, resource_pkey, resource_name = model.util.get_resource_info(
        entity
    )

    snapshot = model.Snapshot()
    snapshot.creation_time = datetime.utcnow()
    snapshot.operation = operation
    snapshot.resource_type = resource_type
    snapshot.resource_pkey = resource_pkey
    snapshot.resource_name = resource_name
    snapshot.user = auth_user
    return snapshot


def create(entity: model.Base, auth_user: Optional[model.User]) -> None:
    assert entity
    snapshot = _create(model.Snapshot.OPERATION_CREATED, entity, auth_user)
    snapshot_factory = _snapshot_factories[snapshot.resource_type]
    snapshot.data = snapshot_factory(entity)
    db.session.add(snapshot)
    _post_to_webhooks(snapshot)


def modify(entity: model.Base, auth_user: Optional[model.User]) -> None:
    assert entity

    table = next(
        (
            cls
            for cls in model.Base._decl_class_registry.values()
            if hasattr(cls, "__table__")
            and cls.__table__.fullname == entity.__table__.fullname
        ),
        None,
    )
    assert table

    snapshot = _create(model.Snapshot.OPERATION_MODIFIED, entity, auth_user)
    snapshot_factory = _snapshot_factories[snapshot.resource_type]

    detached_session = sa.orm.sessionmaker(bind=db.session.get_bind())()
    detached_entity = detached_session.query(table).get(snapshot.resource_pkey)
    assert detached_entity, "Entity not found in DB, have you committed it?"
    detached_snapshot = snapshot_factory(detached_entity)
    detached_session.close()

    active_snapshot = snapshot_factory(entity)

    snapshot.data = diff.get_dict_diff(detached_snapshot, active_snapshot)
    if not snapshot.data:
        return
    db.session.add(snapshot)
    _post_to_webhooks(snapshot)


def delete(entity: model.Base, auth_user: Optional[model.User]) -> None:
    assert entity
    snapshot = _create(model.Snapshot.OPERATION_DELETED, entity, auth_user)
    snapshot_factory = _snapshot_factories[snapshot.resource_type]
    snapshot.data = snapshot_factory(entity)
    db.session.add(snapshot)
    _post_to_webhooks(snapshot)


def merge(
    source_entity: model.Base,
    target_entity: model.Base,
    auth_user: Optional[model.User],
) -> None:
    assert source_entity
    assert target_entity
    snapshot = _create(
        model.Snapshot.OPERATION_MERGED, source_entity, auth_user
    )
    (
        resource_type,
        _resource_pkey,
        resource_name,
    ) = model.util.get_resource_info(target_entity)
    snapshot.data = [resource_type, resource_name]
    db.session.add(snapshot)
    _post_to_webhooks(snapshot)
