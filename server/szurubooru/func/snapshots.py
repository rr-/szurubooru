import datetime
from sqlalchemy.inspection import inspect
from szurubooru import db

def get_tag_snapshot(tag):
    return {
        'names': [tag_name.name for tag_name in tag.names],
        'category': tag.category.name,
        'suggestions': sorted(rel.first_name for rel in tag.suggestions),
        'implications': sorted(rel.first_name for rel in tag.implications),
    }

def get_post_snapshot(post):
    return {
        'source': post.source,
        'safety': post.safety,
        'checksum': post.checksum,
        'tags': sorted([tag.first_name for tag in post.tags]),
        'relations': sorted([rel.post_id for rel in post.relations]),
        'notes': sorted([{
            'polygon': note.polygon,
            'text': note.text,
        } for note in post.notes]),
        'flags': post.flags,
        'featured': post.is_featured,
    }

def get_tag_category_snapshot(category):
    return {
        'name': category.name,
        'color': category.color,
    }

# pylint: disable=invalid-name
serializers = {
    'tag': (
        get_tag_snapshot,
        lambda tag: tag.first_name),
    'tag_category': (
        get_tag_category_snapshot,
        lambda category: category.name),
    'post': (
        get_post_snapshot,
        lambda post: post.post_id),
}

def get_resource_info(entity):
    resource_type = entity.__table__.name
    assert resource_type in serializers

    primary_key = inspect(entity).identity
    assert primary_key is not None
    assert len(primary_key) == 1

    resource_repr = serializers[resource_type][1](entity)
    assert resource_repr

    resource_id = primary_key[0]
    assert resource_id

    return (resource_type, resource_id, resource_repr)

def get_previous_snapshot(snapshot):
    return db.session \
        .query(db.Snapshot) \
        .filter(db.Snapshot.resource_type == snapshot.resource_type) \
        .filter(db.Snapshot.resource_id == snapshot.resource_id) \
        .filter(db.Snapshot.creation_time < snapshot.creation_time) \
        .order_by(db.Snapshot.creation_time.desc()) \
        .limit(1) \
        .first()

def get_snapshots(entity):
    resource_type, resource_id, _ = get_resource_info(entity)
    return db.session \
        .query(db.Snapshot) \
        .filter(db.Snapshot.resource_type == resource_type) \
        .filter(db.Snapshot.resource_id == resource_id) \
        .order_by(db.Snapshot.creation_time.desc()) \
        .all()

def serialize_snapshot(snapshot, earlier_snapshot):
    return {
        'operation': snapshot.operation,
        'type': snapshot.resource_type,
        'id': snapshot.resource_repr,
        'user': snapshot.user.name if snapshot.user else None,
        'data': snapshot.data,
        'earlier-data': earlier_snapshot.data if earlier_snapshot else None,
        'time': snapshot.creation_time,
    }

def get_serialized_history(entity):
    ret = []
    earlier_snapshot = None
    for snapshot in reversed(get_snapshots(entity)):
        ret.insert(0, serialize_snapshot(snapshot, earlier_snapshot))
        earlier_snapshot = snapshot
    return ret

def save(operation, entity, auth_user):
    resource_type, resource_id, resource_repr = get_resource_info(entity)
    now = datetime.datetime.now()

    snapshot = db.Snapshot()
    snapshot.creation_time = now
    snapshot.operation = operation
    snapshot.resource_type = resource_type
    snapshot.resource_id = resource_id
    snapshot.resource_repr = resource_repr
    snapshot.data = serializers[resource_type][0](entity)
    snapshot.user = auth_user

    earlier_snapshots = get_snapshots(entity)

    delta = datetime.timedelta(minutes=10)
    snapshots_left = len(earlier_snapshots)
    while earlier_snapshots:
        last_snapshot = earlier_snapshots.pop(0)
        is_fresh = now - last_snapshot.creation_time <= delta
        if snapshot.data != last_snapshot.data:
            if not is_fresh or last_snapshot.user != auth_user:
                break
        db.session.delete(last_snapshot)
        if snapshot.operation != db.Snapshot.OPERATION_DELETED:
            snapshot.operation = last_snapshot.operation
        snapshots_left -= 1

    if not snapshots_left and operation == db.Snapshot.OPERATION_DELETED:
        pass
    else:
        db.session.add(snapshot)

def create(entity, auth_user):
    save(db.Snapshot.OPERATION_CREATED, entity, auth_user)

def modify(entity, auth_user):
    save(db.Snapshot.OPERATION_MODIFIED, entity, auth_user)

def delete(entity, auth_user):
    save(db.Snapshot.OPERATION_DELETED, entity, auth_user)
