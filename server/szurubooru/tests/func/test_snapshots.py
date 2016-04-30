import datetime
import pytest
from szurubooru import db
from szurubooru.func import snapshots

def test_serializing_post(post_factory, user_factory, tag_factory):
    user = user_factory(name='dummy-user')
    tag1 = tag_factory(names=['dummy-tag1'])
    tag2 = tag_factory(names=['dummy-tag2'])
    post = post_factory(id=1)
    related_post1 = post_factory(id=2)
    related_post2 = post_factory(id=3)
    db.session.add_all([user, tag1, tag2, post, related_post1, related_post2])
    db.session.flush()

    score = db.PostScore()
    score.post = post
    score.user = user
    score.time = datetime.datetime(1997, 1, 1)
    score.score = 1
    favorite = db.PostFavorite()
    favorite.post = post
    favorite.user = user
    favorite.time = datetime.datetime(1997, 1, 1)
    feature = db.PostFeature()
    feature.post = post
    feature.user = user
    feature.time = datetime.datetime(1997, 1, 1)
    note = db.PostNote()
    note.post = post
    note.polygon = [(1, 1), (200, 1), (200, 200), (1, 200)]
    note.text = 'some text'
    db.session.add_all([score])
    db.session.flush()

    post.user = user
    post.checksum = 'deadbeef'
    post.source = 'example.com'
    post.tags.append(tag1)
    post.tags.append(tag2)
    post.relations.append(related_post1)
    post.relations.append(related_post2)
    post.scores.append(score)
    post.favorited_by.append(favorite)
    post.features.append(feature)
    post.notes.append(note)

    assert snapshots.get_post_snapshot(post) == {
        'checksum': 'deadbeef',
        'featured': True,
        'flags': [],
        'notes': [
            {
                'polygon': [(1, 1), (200, 1), (200, 200), (1, 200)],
                'text': 'some text',
            }
        ],
        'relations': [2, 3],
        'safety': 'safe',
        'source': 'example.com',
        'tags': ['dummy-tag1', 'dummy-tag2'],
    }


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
    snapshots.save_entity_creation(tag, user)
    tag.names = [db.TagName('changed')]
    snapshots.save_entity_modification(tag, user)
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
        snapshots.save_entity_creation(tag, user)
    tag.names = [db.TagName('changed')]
    with fake_datetime('14:00:00'):
        snapshots.save_entity_modification(tag, user)
    tag.names = [db.TagName('changed again')]
    with fake_datetime('14:00:01'):
        snapshots.save_entity_modification(tag, user)
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
        snapshots.save_entity_creation(tag, user)
    with fake_datetime('14:00:00'):
        snapshots.save_entity_modification(tag, user)
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
        snapshots.save_entity_creation(tag, user)
    tag.names = [db.TagName('changed')]
    with fake_datetime('13:10:01'):
        snapshots.save_entity_modification(tag, user)
    assert db.session.query(db.Snapshot).count() == 2

def test_not_merging_operations_by_different_users(
        fake_datetime, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user1, user2 = [user_factory(), user_factory()]
    db.session.add_all([tag, user1, user2])
    db.session.flush()
    with fake_datetime('13:00:00'):
        snapshots.save_entity_creation(tag, user1)
        tag.names = [db.TagName('changed')]
        snapshots.save_entity_modification(tag, user2)
    assert db.session.query(db.Snapshot).count() == 2

def test_merging_resets_merging_time_window(
        fake_datetime, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.flush()
    with fake_datetime('13:00:00'):
        snapshots.save_entity_creation(tag, user)
    tag.names = [db.TagName('changed')]
    with fake_datetime('13:09:59'):
        snapshots.save_entity_modification(tag, user)
    tag.names = [db.TagName('changed again')]
    with fake_datetime('13:19:59'):
        snapshots.save_entity_modification(tag, user)
    results = db.session.query(db.Snapshot).all()
    assert len(results) == 1
    assert results[0].data['names'] == ['changed again']

@pytest.mark.parametrize(
    'initial_operation',
    [snapshots.save_entity_creation, snapshots.save_entity_modification])
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
        snapshots.save_entity_modification(tag, user)
    tag.names = [db.TagName('changed again')]
    with fake_datetime('14:00:01'):
        snapshots.save_entity_deletion(tag, user)
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
    'expected_operation',
    [snapshots.save_entity_creation, snapshots.save_entity_modification])
def test_merging_deletion_all_the_way_deletes_all_snapshots(
        fake_datetime, tag_factory, user_factory, expected_operation):
    tag = tag_factory(names=['dummy'])
    user = user_factory()
    db.session.add_all([tag, user])
    db.session.flush()
    with fake_datetime('13:00:00'):
        snapshots.save_entity_creation(tag, user)
    tag.names = [db.TagName('changed')]
    with fake_datetime('13:00:01'):
        snapshots.save_entity_modification(tag, user)
    tag.names = [db.TagName('changed again')]
    with fake_datetime('13:00:02'):
        snapshots.save_entity_deletion(tag, user)
    assert db.session.query(db.Snapshot).count() == 0

def test_get_serialized_history(fake_datetime, tag_factory, user_factory):
    tag = tag_factory(names=['dummy'])
    user = user_factory(name='the-user')
    db.session.add_all([tag, user])
    db.session.flush()
    with fake_datetime('2016-04-19 13:00:00'):
        snapshots.save_entity_creation(tag, user)
    tag.names = [db.TagName('changed')]
    db.session.flush()
    with fake_datetime('2016-04-19 13:10:01'):
        snapshots.save_entity_modification(tag, user)
    assert snapshots.get_serialized_history(tag) == [
        {
            'operation': 'modified',
            'time': datetime.datetime(2016, 4, 19, 13, 10, 1),
            'type': 'tag',
            'id': 'changed',
            'user': 'the-user',
            'data': {
                'names': ['changed'],
                'category': 'dummy',
                'suggestions': [],
                'implications': [],
            },
            'earlier-data': {
                'names': ['dummy'],
                'category': 'dummy',
                'suggestions': [],
                'implications': [],
            },
        },
        {
            'operation': 'created',
            'time': datetime.datetime(2016, 4, 19, 13, 0, 0),
            'type': 'tag',
            'id': 'dummy',
            'user': 'the-user',
            'data': {
                'names': ['dummy'],
                'category': 'dummy',
                'suggestions': [],
                'implications': [],
            },
            'earlier-data': None,
        },
    ]
