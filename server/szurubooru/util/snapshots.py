import datetime
from sqlalchemy.inspection import inspect
from szurubooru import db

def get_tag_snapshot(tag):
    ret = {
        'names': [tag_name.name for tag_name in tag.names],
        'category': tag.category.name
    }
    if tag.suggestions:
        ret['suggestions'] = sorted(rel.first_name for rel in tag.suggestions)
    if tag.implications:
        ret['implications'] = sorted(rel.first_name for rel in tag.implications)
    return ret

# pylint: disable=invalid-name
serializers = {
    'tag': get_tag_snapshot,
}

def save(operation, entity, auth_user):
    table_name = entity.__table__.name
    primary_key = inspect(entity).identity
    assert table_name in serializers
    assert len(primary_key) == 1
    primary_key = primary_key[0]
    now = datetime.datetime.now()

    snapshot = db.Snapshot()
    snapshot.creation_time = now
    snapshot.operation = operation
    snapshot.resource_type = table_name
    snapshot.resource_id = primary_key
    snapshot.data = serializers[table_name](entity)
    snapshot.user = auth_user

    earlier_snapshots = db.session \
        .query(db.Snapshot) \
        .filter(db.Snapshot.resource_type == table_name) \
        .filter(db.Snapshot.resource_id == primary_key) \
        .order_by(db.Snapshot.creation_time.desc()) \
        .all()

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
