import datetime
import pytest
from szurubooru import db
from szurubooru.func import snapshots

def test_serializing_tag(tag_factory):
    tag = tag_factory(names=['main_name', 'alias'], category_name='dummy')
    assert snapshots.get_tag_snapshot(tag) == {
        'names': ['main_name', 'alias'],
        'category': 'dummy',
        'suggestions': [],
        'implications': [],
    }

    tag = tag_factory(names=['main_name', 'alias'], category_name='dummy')
    imp1 = tag_factory(names=['imp1_main_name', 'imp1_alias'])
    imp2 = tag_factory(names=['imp2_main_name', 'imp2_alias'])
    sug1 = tag_factory(names=['sug1_main_name', 'sug1_alias'])
    sug2 = tag_factory(names=['sug2_main_name', 'sug2_alias'])
    db.session.add_all([imp1, imp2, sug1, sug2])
    tag.implications = [imp1, imp2]
    tag.suggestions = [sug1, sug2]
    db.session.flush()
    assert snapshots.get_tag_snapshot(tag) == {
        'names': ['main_name', 'alias'],
        'category': 'dummy',
        'implications': ['imp1_main_name', 'imp2_main_name'],
        'suggestions': ['sug1_main_name', 'sug2_main_name'],
    }

def test_serializing_tag_category(tag_category_factory):
    category = tag_category_factory(name='name', color='color')
    assert snapshots.get_tag_category_snapshot(category) == {
        'name': 'name',
        'color': 'color',
    }

def test_merging_modification_to_creation(tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.flush()
    snapshots.create(tag, user)
    tag.names = [db.TagName('changed')]
    snapshots.modify(tag, user)
    results = db.session.query(db.Snapshot).all()
    assert len(results) == 1
    assert results[0].operation == db.Snapshot.OPERATION_CREATED
    assert results[0].data['names'] == ['changed']

def test_merging_modifications(fake_datetime, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(tag, user)
    tag.names = [db.TagName('changed')]
    with fake_datetime('14:00:00'):
        snapshots.modify(tag, user)
    tag.names = [db.TagName('changed again')]
    with fake_datetime('14:00:01'):
        snapshots.modify(tag, user)
    results = db.session.query(db.Snapshot).all()
    assert len(results) == 2
    assert results[0].operation == db.Snapshot.OPERATION_CREATED
    assert results[1].operation == db.Snapshot.OPERATION_MODIFIED
    assert results[0].data['names'] == ['dummy']
    assert results[1].data['names'] == ['changed again']

def test_not_adding_snapshot_if_data_doesnt_change(
        fake_datetime, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(tag, user)
    with fake_datetime('14:00:00'):
        snapshots.modify(tag, user)
    results = db.session.query(db.Snapshot).all()
    assert len(results) == 1
    assert results[0].operation == db.Snapshot.OPERATION_CREATED
    assert results[0].data['names'] == ['dummy']

def test_not_merging_due_to_time_difference(
        fake_datetime, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(tag, user)
    tag.names = [db.TagName('changed')]
    with fake_datetime('13:10:01'):
        snapshots.modify(tag, user)
    assert db.session.query(db.Snapshot).count() == 2

def test_not_merging_operations_by_different_users(
        fake_datetime, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user1, user2 = [user_factory(), user_factory()]
    db.session.add_all([tag, user1, user2])
    db.session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(tag, user1)
        tag.names = [db.TagName('changed')]
        snapshots.modify(tag, user2)
    assert db.session.query(db.Snapshot).count() == 2

def test_merging_resets_merging_time_window(
        fake_datetime, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(tag, user)
    tag.names = [db.TagName('changed')]
    with fake_datetime('13:09:59'):
        snapshots.modify(tag, user)
    tag.names = [db.TagName('changed again')]
    with fake_datetime('13:19:59'):
        snapshots.modify(tag, user)
    results = db.session.query(db.Snapshot).all()
    assert len(results) == 1
    assert results[0].data['names'] == ['changed again']

@pytest.mark.parametrize(
    'initial_operation', [snapshots.create, snapshots.modify])
def test_merging_deletion_to_modification_or_creation(
        fake_datetime, tag_factory, user_factory, initial_operation):
    tag = tag_factory(names=['dummy'], category_name='dummy')
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.flush()
    with fake_datetime('13:00:00'):
        initial_operation(tag, user)
    tag.names = [db.TagName('changed')]
    with fake_datetime('14:00:00'):
        snapshots.modify(tag, user)
    tag.names = [db.TagName('changed again')]
    with fake_datetime('14:00:01'):
        snapshots.delete(tag, user)
    assert db.session.query(db.Snapshot).count() == 2
    results = db.session \
        .query(db.Snapshot) \
        .order_by(db.Snapshot.snapshot_id.asc()) \
        .all()
    assert results[1].operation == db.Snapshot.OPERATION_DELETED
    assert results[1].data == {
        'names': ['changed again'],
        'category': 'dummy',
        'suggestions': [],
        'implications': [],
    }

@pytest.mark.parametrize(
    'expected_operation', [snapshots.create, snapshots.modify])
def test_merging_deletion_all_the_way_deletes_all_snapshots(
        fake_datetime, tag_factory, user_factory, expected_operation):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.flush()
    with fake_datetime('13:00:00'):
        snapshots.create(tag, user)
    tag.names = [db.TagName('changed')]
    with fake_datetime('13:00:01'):
        snapshots.modify(tag, user)
    tag.names = [db.TagName('changed again')]
    with fake_datetime('13:00:02'):
        snapshots.delete(tag, user)
    assert db.session.query(db.Snapshot).count() == 0

def test_get_data(fake_datetime, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.flush()
    with fake_datetime('2016-04-19 13:00:00'):
        snapshots.create(tag, user)
    tag.names = [db.TagName('changed')]
    with fake_datetime('2016-04-19 13:10:01'):
        snapshots.modify(tag, user)
    assert snapshots.get_data(tag) == [
        {
            'time': datetime.datetime(2016, 4, 19, 13, 10, 1),
            'data': {
                'names': ['changed'],
                'category': 'dummy',
                'suggestions': [],
                'implications': [],
            },
        },
        {
            'time': datetime.datetime(2016, 4, 19, 13, 0, 0),
            'data': {
                'names': ['dummy'],
                'category': 'dummy',
                'suggestions': [],
                'implications': [],
            },
        },
    ]
