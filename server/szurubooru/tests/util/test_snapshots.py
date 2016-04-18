import datetime
import pytest
from szurubooru import db
from szurubooru.util import snapshots

def test_serializing_tag(session, tag_factory):
    tag = tag_factory(names=['main_name', 'alias'], category_name='dummy')
    assert snapshots.get_tag_snapshot(tag) == {
        'names': ['main_name', 'alias'],
        'category': 'dummy'
    }

    tag = tag_factory(names=['main_name', 'alias'], category_name='dummy')
    imp1 = tag_factory(names=['imp1_main_name', 'imp1_alias'])
    imp2 = tag_factory(names=['imp2_main_name', 'imp2_alias'])
    sug1 = tag_factory(names=['sug1_main_name', 'sug1_alias'])
    sug2 = tag_factory(names=['sug2_main_name', 'sug2_alias'])
    session.add_all([imp1, imp2, sug1, sug2])
    tag.implications = [imp1, imp2]
    tag.suggestions = [sug1, sug2]
    session.flush()
    assert snapshots.get_tag_snapshot(tag) == {
        'names': ['main_name', 'alias'],
        'category': 'dummy',
        'implications': ['imp1_main_name', 'imp2_main_name'],
        'suggestions': ['sug1_main_name', 'sug2_main_name'],
    }

def test_merging_modification_to_creation(session, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    session.add_all([tag, user])
    session.flush()
    snapshots.create(session, tag, user)
    session.flush()
    tag.names = [db.TagName('changed')]
    snapshots.modify(session, tag, user)
    session.flush()
    results = session.query(db.Snapshot).all()
    assert len(results) == 1
    assert results[0].operation == db.Snapshot.OPERATION_CREATED
    assert results[0].data['names'] == ['changed']

def test_merging_modifications(
        fake_datetime, session, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    session.add_all([tag, user])
    session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(session, tag, user)
    session.flush()
    tag.names = [db.TagName('changed')]
    with fake_datetime('14:00:00'):
        snapshots.modify(session, tag, user)
    session.flush()
    tag.names = [db.TagName('changed again')]
    with fake_datetime('14:00:01'):
        snapshots.modify(session, tag, user)
    session.flush()
    results = session.query(db.Snapshot).all()
    assert len(results) == 2
    assert results[0].operation == db.Snapshot.OPERATION_CREATED
    assert results[1].operation == db.Snapshot.OPERATION_MODIFIED
    assert results[0].data['names'] == ['dummy']
    assert results[1].data['names'] == ['changed again']

def test_not_adding_snapshot_if_data_doesnt_change(
        fake_datetime, session, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    session.add_all([tag, user])
    session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(session, tag, user)
    session.flush()
    with fake_datetime('14:00:00'):
        snapshots.modify(session, tag, user)
    session.flush()
    results = session.query(db.Snapshot).all()
    assert len(results) == 1
    assert results[0].operation == db.Snapshot.OPERATION_CREATED
    assert results[0].data['names'] == ['dummy']

def test_not_merging_due_to_time_difference(
        fake_datetime, session, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    session.add_all([tag, user])
    session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(session, tag, user)
    session.flush()
    tag.names = [db.TagName('changed')]
    with fake_datetime('13:10:01'):
        snapshots.modify(session, tag, user)
    session.flush()
    assert session.query(db.Snapshot).count() == 2

def test_not_merging_operations_by_different_users(
        fake_datetime, session, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user1, user2 = [user_factory(), user_factory()]
    session.add_all([tag, user1, user2])
    session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(session, tag, user1)
        session.flush()
        tag.names = [db.TagName('changed')]
        snapshots.modify(session, tag, user2)
        session.flush()
    assert session.query(db.Snapshot).count() == 2

def test_merging_resets_merging_time_window(
        fake_datetime, session, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    session.add_all([tag, user])
    session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(session, tag, user)
    session.flush()
    tag.names = [db.TagName('changed')]
    with fake_datetime('13:09:59'):
        snapshots.modify(session, tag, user)
    session.flush()
    tag.names = [db.TagName('changed again')]
    with fake_datetime('13:19:59'):
        snapshots.modify(session, tag, user)
    session.flush()
    results = session.query(db.Snapshot).all()
    assert len(results) == 1
    assert results[0].data['names'] == ['changed again']

@pytest.mark.parametrize(
    'initial_operation', [snapshots.create, snapshots.modify])
def test_merging_deletion_to_modification_or_creation(
        fake_datetime, session, tag_factory, user_factory, initial_operation):
    tag = tag_factory(names=['dummy'], category_name='dummy')
    user = user_factory()
    session.add_all([tag, user])
    session.flush()
    with fake_datetime('13:00:00'):
        initial_operation(session, tag, user)
    session.flush()
    tag.names = [db.TagName('changed')]
    with fake_datetime('14:00:00'):
        snapshots.modify(session, tag, user)
    session.flush()
    tag.names = [db.TagName('changed again')]
    with fake_datetime('14:00:01'):
        snapshots.delete(session, tag, user)
    session.flush()
    assert session.query(db.Snapshot).count() == 2
    results = session.query(db.Snapshot) \
        .order_by(db.Snapshot.snapshot_id.asc()) \
        .all()
    assert results[1].operation == db.Snapshot.OPERATION_DELETED
    assert results[1].data == {'names': ['changed again'], 'category': 'dummy'}

@pytest.mark.parametrize(
    'expected_operation', [snapshots.create, snapshots.modify])
def test_merging_deletion_all_the_way_deletes_all_snapshots(
        fake_datetime, session, tag_factory, user_factory, expected_operation):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    session.add_all([tag, user])
    session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(session, tag, user)
    session.flush()
    tag.names = [db.TagName('changed')]
    with fake_datetime('13:00:01'):
        snapshots.modify(session, tag, user)
    session.flush()
    tag.names = [db.TagName('changed again')]
    with fake_datetime('13:00:02'):
        snapshots.delete(session, tag, user)
    session.flush()
    assert session.query(db.Snapshot).count() == 0
