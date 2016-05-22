import datetime
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
        'default': True if category.default else False,
    }

# pylint: disable=invalid-name
serializers = {
    'tag': get_tag_snapshot,
    'tag_category': get_tag_category_snapshot,
    'post': get_post_snapshot,
}

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
    resource_type, resource_id, _ = db.util.get_resource_info(entity)
    return db.session \
        .query(db.Snapshot) \
        .filter(db.Snapshot.resource_type == resource_type) \
        .filter(db.Snapshot.resource_id == resource_id) \
        .order_by(db.Snapshot.creation_time.desc()) \
        .all()

def serialize_snapshot(snapshot, earlier_snapshot=()):
    if earlier_snapshot is ():
        earlier_snapshot = get_previous_snapshot(snapshot)
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
    if not entity:
        return []
    ret = []
    earlier_snapshot = None
    for snapshot in reversed(get_snapshots(entity)):
        ret.insert(0, serialize_snapshot(snapshot, earlier_snapshot))
        earlier_snapshot = snapshot
    return ret

def _save(operation, entity, auth_user):
    resource_type, resource_id, resource_repr = db.util.get_resource_info(entity)
    now = datetime.datetime.now()

    snapshot = db.Snapshot()
    snapshot.creation_time = now
    snapshot.operation = operation
    snapshot.resource_type = resource_type
    snapshot.resource_id = resource_id
    snapshot.resource_repr = resource_repr
    snapshot.data = serializers[resource_type](entity)
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

def save_entity_creation(entity, auth_user):
    _save(db.Snapshot.OPERATION_CREATED, entity, auth_user)

def save_entity_modification(entity, auth_user):
    _save(db.Snapshot.OPERATION_MODIFIED, entity, auth_user)

def save_entity_deletion(entity, auth_user):
    _save(db.Snapshot.OPERATION_DELETED, entity, auth_user)
