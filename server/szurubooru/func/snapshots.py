from datetime import datetime
from szurubooru import db
from szurubooru.func import diff, users


def get_tag_category_snapshot(category):
    assert category
    return {
        'name': category.name,
        'color': category.color,
        'default': True if category.default else False,
    }


def get_tag_snapshot(tag):
    assert tag
    return {
        'names': [tag_name.name for tag_name in tag.names],
        'category': tag.category.name,
        'suggestions': sorted(rel.first_name for rel in tag.suggestions),
        'implications': sorted(rel.first_name for rel in tag.implications),
    }


def get_post_snapshot(post):
    assert post
    return {
        'source': post.source,
        'safety': post.safety,
        'checksum': post.checksum,
        'flags': post.flags,
        'featured': post.is_featured,
        'tags': sorted([tag.first_name for tag in post.tags]),
        'relations': sorted([rel.post_id for rel in post.relations]),
        'notes': sorted([{
            'polygon': [[point[0], point[1]] for point in note.polygon],
            'text': note.text,
        } for note in post.notes], key=lambda x: x['polygon']),
    }


_snapshot_factories = {
    # lambdas allow mocking target functions in the tests
    # pylint: disable=unnecessary-lambda
    'tag_category': lambda entity: get_tag_category_snapshot(entity),
    'tag': lambda entity: get_tag_snapshot(entity),
    'post': lambda entity: get_post_snapshot(entity),
}


def serialize_snapshot(snapshot, auth_user):
    assert snapshot
    return {
        'operation': snapshot.operation,
        'type': snapshot.resource_type,
        'id': snapshot.resource_name,
        'user': users.serialize_micro_user(snapshot.user, auth_user),
        'data': snapshot.data,
        'time': snapshot.creation_time,
    }


def _create(operation, entity, auth_user):
    resource_type, resource_pkey, resource_name = (
        db.util.get_resource_info(entity))

    snapshot = db.Snapshot()
    snapshot.creation_time = datetime.utcnow()
    snapshot.operation = operation
    snapshot.resource_type = resource_type
    snapshot.resource_pkey = resource_pkey
    snapshot.resource_name = resource_name
    snapshot.user = auth_user
    return snapshot


def create(entity, auth_user):
    assert entity
    snapshot = _create(db.Snapshot.OPERATION_CREATED, entity, auth_user)
    snapshot_factory = _snapshot_factories[snapshot.resource_type]
    snapshot.data = snapshot_factory(entity)
    db.session.add(snapshot)


# pylint: disable=protected-access
def modify(entity, auth_user):
    assert entity

    model = next(
        (
            model
            for model in db.Base._decl_class_registry.values()
            if hasattr(model, '__table__')
            and model.__table__.fullname == entity.__table__.fullname
        ),
        None)
    assert model

    snapshot = _create(db.Snapshot.OPERATION_MODIFIED, entity, auth_user)
    snapshot_factory = _snapshot_factories[snapshot.resource_type]

    detached_session = db.sessionmaker()
    detached_entity = detached_session.query(model).get(snapshot.resource_pkey)
    assert detached_entity, 'Entity not found in DB, have you committed it?'
    detached_snapshot = snapshot_factory(detached_entity)
    detached_session.close()

    active_snapshot = snapshot_factory(entity)

    snapshot.data = diff.get_dict_diff(detached_snapshot, active_snapshot)
    if not snapshot.data:
        return
    db.session.add(snapshot)


def delete(entity, auth_user):
    assert entity
    snapshot = _create(db.Snapshot.OPERATION_DELETED, entity, auth_user)
    snapshot_factory = _snapshot_factories[snapshot.resource_type]
    snapshot.data = snapshot_factory(entity)
    db.session.add(snapshot)


def merge(source_entity, target_entity, auth_user):
    assert source_entity
    assert target_entity
    snapshot = _create(db.Snapshot.OPERATION_MERGED, source_entity, auth_user)
    resource_type, _resource_pkey, resource_name = (
        db.util.get_resource_info(target_entity))
    snapshot.data = [resource_type, resource_name]
    db.session.add(snapshot)
