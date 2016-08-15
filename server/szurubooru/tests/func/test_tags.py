import os
import json
from unittest.mock import patch
from datetime import datetime
import pytest
from szurubooru import db
from szurubooru.func import tags, tag_categories, cache, snapshots


@pytest.fixture(autouse=True)
def purge_cache():
    cache.purge()


def _assert_tag_siblings(result, expected_names_and_occurrences):
    actual_names_and_occurences = [
        (tag.names[0].name, occurrences) for tag, occurrences in result]
    assert actual_names_and_occurences == expected_names_and_occurrences


@pytest.mark.parametrize('input,expected_tag_names', [
    ([('a', 'a', True), ('b', 'b', False), ('c', 'c', False)], list('bca')),
    ([('c', 'a', True), ('b', 'b', False), ('a', 'c', False)], list('bac')),
    ([('a', 'c', True), ('b', 'b', False), ('c', 'a', False)], list('cba')),
    ([('a', 'c', False), ('b', 'b', False), ('c', 'a', True)], list('bac')),
])
def test_sort_tags(
        input, expected_tag_names, tag_factory, tag_category_factory):
    db_tags = []
    for tag in input:
        tag_name, category_name, category_is_default = tag
        db_tags.append(
            tag_factory(
                names=[tag_name],
                category=tag_category_factory(
                    name=category_name, default=category_is_default)))
    db.session.add_all(db_tags)
    actual_tag_names = [tag.names[0].name for tag in tags.sort_tags(db_tags)]
    assert actual_tag_names == expected_tag_names


def test_serialize_tag_when_empty():
    assert tags.serialize_tag(None, None) is None


def test_serialize_tag(post_factory, tag_factory, tag_category_factory):
    with patch('szurubooru.func.snapshots.get_serialized_history'):
        snapshots.get_serialized_history.return_value = 'snapshot history'
        tag = tag_factory(
            names=['tag1', 'tag2'],
            category=tag_category_factory(name='cat'))
        tag.tag_id = 1
        tag.description = 'description'
        tag.suggestions = [
            tag_factory(names=['sug1']), tag_factory(names=['sug2'])]
        tag.implications = [
            tag_factory(names=['impl1']), tag_factory(names=['impl2'])]
        tag.last_edit_time = datetime(1998, 1, 1)
        post1 = post_factory()
        post2 = post_factory()
        post1.tags = [tag]
        post2.tags = [tag]
        db.session.add_all([tag, post1, post2])
        db.session.flush()
        result = tags.serialize_tag(tag)
        result['suggestions'].sort()
        result['implications'].sort()
        assert result == {
            'names': ['tag1', 'tag2'],
            'version': 1,
            'category': 'cat',
            'creationTime': datetime(1996, 1, 1, 0, 0),
            'lastEditTime': datetime(1998, 1, 1, 0, 0),
            'description': 'description',
            'suggestions': ['sug1', 'sug2'],
            'implications': ['impl1', 'impl2'],
            'usages': 2,
            'snapshots': 'snapshot history',
        }


def test_export_to_json(
        tmpdir,
        query_counter,
        config_injector,
        post_factory,
        tag_factory,
        tag_category_factory):
    config_injector({'data_dir': str(tmpdir)})
    cat1 = tag_category_factory(name='cat1', color='black')
    cat2 = tag_category_factory(name='cat2', color='white')
    tag = tag_factory(names=['alias1', 'alias2'], category=cat2)
    tag.suggestions = [
        tag_factory(names=['sug1'], category=cat1),
        tag_factory(names=['sug2'], category=cat1),
    ]
    tag.implications = [
        tag_factory(names=['imp1'], category=cat1),
        tag_factory(names=['imp2'], category=cat1),
    ]
    post = post_factory()
    post.tags = [tag]
    db.session.add_all([post, tag])
    db.session.flush()

    with query_counter:
        tags.export_to_json()
        assert len(query_counter.statements) == 5

    export_path = os.path.join(str(tmpdir), 'tags.json')
    assert os.path.exists(export_path)
    with open(export_path, 'r') as handle:
        actual_json = json.loads(handle.read())
        assert actual_json['tags']
        assert actual_json['categories']
        actual_json['tags'].sort(key=lambda tag: tag['names'][0])
        actual_json['categories'].sort(key=lambda category: category['name'])
        assert actual_json == {
            'tags': [
                {
                    'names': ['alias1', 'alias2'],
                    'usages': 1,
                    'category': 'cat2',
                    'suggestions': ['sug1', 'sug2'],
                    'implications': ['imp1', 'imp2'],
                },
                {'names': ['imp1'], 'usages': 0, 'category': 'cat1'},
                {'names': ['imp2'], 'usages': 0, 'category': 'cat1'},
                {'names': ['sug1'], 'usages': 0, 'category': 'cat1'},
                {'names': ['sug2'], 'usages': 0, 'category': 'cat1'},
            ],
            'categories': [
                {'name': 'cat1', 'color': 'black'},
                {'name': 'cat2', 'color': 'white'},
            ]
        }


@pytest.mark.parametrize('name_to_search,expected_to_find', [
    ('name', True),
    ('NAME', True),
    ('alias', True),
    ('ALIAS', True),
    ('-', False),
])
def test_try_get_tag_by_name(name_to_search, expected_to_find, tag_factory):
    tag = tag_factory(names=['name', 'ALIAS'])
    db.session.add(tag)
    if expected_to_find:
        assert tags.try_get_tag_by_name(name_to_search) == tag
    else:
        assert tags.try_get_tag_by_name(name_to_search) is None


@pytest.mark.parametrize('name_to_search,expected_to_find', [
    ('name', True),
    ('NAME', True),
    ('alias', True),
    ('ALIAS', True),
    ('-', False),
])
def test_get_tag_by_name(name_to_search, expected_to_find, tag_factory):
    tag = tag_factory(names=['name', 'ALIAS'])
    db.session.add(tag)
    if expected_to_find:
        assert tags.get_tag_by_name(name_to_search) == tag
    else:
        with pytest.raises(tags.TagNotFoundError):
            tags.get_tag_by_name(name_to_search)


@pytest.mark.parametrize('names,expected_indexes', [
    ([], []),
    (['name1'], [0]),
    (['NAME1'], [0]),
    (['alias1'], [0]),
    (['ALIAS1'], [0]),
    (['name2'], [1]),
    (['name1', 'name1'], [0]),
    (['name1', 'NAME1'], [0]),
    (['name1', 'alias1'], [0]),
    (['name1', 'alias2'], [0, 1]),
    (['NAME1', 'alias2'], [0, 1]),
    (['name1', 'ALIAS2'], [0, 1]),
    (['name2', 'alias1'], [0, 1]),
])
def test_get_tag_by_names(names, expected_indexes, tag_factory):
    input_tags = [
        tag_factory(names=['name1', 'ALIAS1']),
        tag_factory(names=['name2', 'ALIAS2']),
    ]
    db.session.add_all(input_tags)
    db.session.flush()
    expected_ids = [input_tags[i].tag_id for i in expected_indexes]
    actual_ids = [tag.tag_id for tag in tags.get_tags_by_names(names)]
    assert actual_ids == expected_ids


@pytest.mark.parametrize(
    'names,expected_indexes,expected_created_names', [
        ([], [], []),
        (['name1'], [0], []),
        (['NAME1'], [0], []),
        (['alias1'], [0], []),
        (['ALIAS1'], [0], []),
        (['name2'], [1], []),
        (['name1', 'name1'], [0], []),
        (['name1', 'NAME1'], [0], []),
        (['name1', 'alias1'], [0], []),
        (['name1', 'alias2'], [0, 1], []),
        (['NAME1', 'alias2'], [0, 1], []),
        (['name1', 'ALIAS2'], [0, 1], []),
        (['name2', 'alias1'], [0, 1], []),
        (['new'], [], ['new']),
        (['new', 'name1'], [0], ['new']),
        (['new', 'NAME1'], [0], ['new']),
        (['new', 'alias1'], [0], ['new']),
        (['new', 'ALIAS1'], [0], ['new']),
        (['new', 'name2'], [1], ['new']),
        (['new', 'name1', 'name1'], [0], ['new']),
        (['new', 'name1', 'NAME1'], [0], ['new']),
        (['new', 'name1', 'alias1'], [0], ['new']),
        (['new', 'name1', 'alias2'], [0, 1], ['new']),
        (['new', 'NAME1', 'alias2'], [0, 1], ['new']),
        (['new', 'name1', 'ALIAS2'], [0, 1], ['new']),
        (['new', 'name2', 'alias1'], [0, 1], ['new']),
        (['new', 'new'], [], ['new']),
        (['new', 'NEW'], [], ['new']),
        (['new', 'new2'], [], ['new', 'new2']),
    ])
def test_get_or_create_tags_by_names(
        names,
        expected_indexes,
        expected_created_names,
        tag_factory,
        tag_category_factory,
        config_injector):
    config_injector({'tag_name_regex': '.*'})
    category = tag_category_factory()
    input_tags = [
        tag_factory(names=['name1', 'ALIAS1'], category=category),
        tag_factory(names=['name2', 'ALIAS2'], category=category),
    ]
    db.session.add_all(input_tags)
    result = tags.get_or_create_tags_by_names(names)
    expected_ids = [input_tags[i].tag_id for i in expected_indexes]
    actual_ids = [tag.tag_id for tag in result[0]]
    actual_created_names = [tag.names[0].name for tag in result[1]]
    assert actual_ids == expected_ids
    assert actual_created_names == expected_created_names


def test_get_tag_siblings_for_unused(tag_factory):
    tag = tag_factory(names=['tag'])
    db.session.add(tag)
    db.session.flush()
    _assert_tag_siblings(tags.get_tag_siblings(tag), [])


def test_get_tag_siblings_for_used_alone(tag_factory, post_factory):
    tag = tag_factory(names=['tag'])
    post = post_factory()
    post.tags = [tag]
    db.session.add_all([post, tag])
    db.session.flush()
    _assert_tag_siblings(tags.get_tag_siblings(tag), [])


def test_get_tag_siblings_for_used_with_others(tag_factory, post_factory):
    tag1 = tag_factory(names=['t1'])
    tag2 = tag_factory(names=['t2'])
    post = post_factory()
    post.tags = [tag1, tag2]
    db.session.add_all([post, tag1, tag2])
    db.session.flush()
    _assert_tag_siblings(tags.get_tag_siblings(tag1), [('t2', 1)])
    _assert_tag_siblings(tags.get_tag_siblings(tag2), [('t1', 1)])


def test_get_tag_siblings_used_for_multiple_others(tag_factory, post_factory):
    tag1 = tag_factory(names=['t1'])
    tag2 = tag_factory(names=['t2'])
    tag3 = tag_factory(names=['t3'])
    post1 = post_factory()
    post2 = post_factory()
    post3 = post_factory()
    post4 = post_factory()
    post1.tags = [tag1, tag2, tag3]
    post2.tags = [tag1, tag3]
    post3.tags = [tag2]
    post4.tags = [tag2]
    db.session.add_all([post1, post2, post3, post4, tag1, tag2, tag3])
    db.session.flush()
    _assert_tag_siblings(tags.get_tag_siblings(tag1), [('t3', 2), ('t2', 1)])
    _assert_tag_siblings(tags.get_tag_siblings(tag2), [('t1', 1), ('t3', 1)])
    # even though tag2 is used more widely, tag1 is more relevant to tag3
    _assert_tag_siblings(tags.get_tag_siblings(tag3), [('t1', 2), ('t2', 1)])


def test_delete(tag_factory):
    tag = tag_factory(names=['tag'])
    tag.suggestions = [tag_factory(names=['sug'])]
    tag.implications = [tag_factory(names=['imp'])]
    db.session.add(tag)
    db.session.flush()
    assert db.session.query(db.Tag).count() == 3
    tags.delete(tag)
    assert db.session.query(db.Tag).count() == 2


def test_merge_tags_without_usages(tag_factory):
    source_tag = tag_factory(names=['source'])
    target_tag = tag_factory(names=['target'])
    db.session.add_all([source_tag, target_tag])
    db.session.commit()
    tags.merge_tags(source_tag, target_tag)
    db.session.commit()
    assert tags.try_get_tag_by_name('source') is None
    tag = tags.get_tag_by_name('target')
    assert tag is not None


def test_merge_tags_with_usages(tag_factory, post_factory):
    source_tag = tag_factory(names=['source'])
    target_tag = tag_factory(names=['target'])
    post = post_factory()
    post.tags = [source_tag]
    db.session.add_all([source_tag, target_tag, post])
    db.session.commit()
    assert source_tag.post_count == 1
    assert target_tag.post_count == 0
    tags.merge_tags(source_tag, target_tag)
    db.session.commit()
    assert tags.try_get_tag_by_name('source') is None
    assert tags.get_tag_by_name('target').post_count == 1


def test_merge_tags_with_itself(tag_factory):
    source_tag = tag_factory(names=['source'])
    db.session.add(source_tag)
    db.session.commit()
    with pytest.raises(tags.InvalidTagRelationError):
        tags.merge_tags(source_tag, source_tag)


def test_merge_tags_with_its_child_relation(tag_factory, post_factory):
    source_tag = tag_factory(names=['source'])
    target_tag = tag_factory(names=['target'])
    source_tag.suggestions = [target_tag]
    source_tag.implications = [target_tag]
    post = post_factory()
    post.tags = [source_tag, target_tag]
    db.session.add_all([source_tag, post])
    db.session.commit()
    tags.merge_tags(source_tag, target_tag)
    db.session.commit()
    assert tags.try_get_tag_by_name('source') is None
    assert tags.get_tag_by_name('target').post_count == 1


def test_merge_tags_with_its_parent_relation(tag_factory, post_factory):
    source_tag = tag_factory(names=['source'])
    target_tag = tag_factory(names=['target'])
    target_tag.suggestions = [source_tag]
    target_tag.implications = [source_tag]
    post = post_factory()
    post.tags = [source_tag, target_tag]
    db.session.add_all([source_tag, target_tag, post])
    db.session.commit()
    tags.merge_tags(source_tag, target_tag)
    db.session.commit()
    assert tags.try_get_tag_by_name('source') is None
    assert tags.get_tag_by_name('target').post_count == 1


def test_merge_tags_clears_relations(tag_factory):
    source_tag = tag_factory(names=['source'])
    target_tag = tag_factory(names=['target'])
    referring_tag = tag_factory(names=['parent'])
    referring_tag.suggestions = [source_tag]
    referring_tag.implications = [source_tag]
    db.session.add_all([source_tag, target_tag, referring_tag])
    db.session.commit()
    assert tags.try_get_tag_by_name('parent').implications != []
    assert tags.try_get_tag_by_name('parent').suggestions != []
    tags.merge_tags(source_tag, target_tag)
    db.session.commit()
    assert tags.try_get_tag_by_name('source') is None
    assert tags.try_get_tag_by_name('parent').implications == []
    assert tags.try_get_tag_by_name('parent').suggestions == []


def test_merge_tags_when_target_exists(tag_factory, post_factory):
    source_tag = tag_factory(names=['source'])
    target_tag = tag_factory(names=['target'])
    post = post_factory()
    post.tags = [source_tag, target_tag]
    db.session.add_all([source_tag, target_tag, post])
    db.session.commit()
    assert source_tag.post_count == 1
    assert target_tag.post_count == 1
    tags.merge_tags(source_tag, target_tag)
    db.session.commit()
    assert tags.try_get_tag_by_name('source') is None
    assert tags.get_tag_by_name('target').post_count == 1


def test_create_tag(fake_datetime):
    with patch('szurubooru.func.tags.update_tag_names'), \
            patch('szurubooru.func.tags.update_tag_category_name'), \
            patch('szurubooru.func.tags.update_tag_suggestions'), \
            patch('szurubooru.func.tags.update_tag_implications'), \
            fake_datetime('1997-01-01'):
        tag = tags.create_tag(['name'], 'cat', ['sug'], ['imp'])
        assert tag.creation_time == datetime(1997, 1, 1)
        assert tag.last_edit_time is None
        tags.update_tag_names.assert_called_once_with(tag, ['name'])
        tags.update_tag_category_name.assert_called_once_with(tag, 'cat')
        tags.update_tag_suggestions.assert_called_once_with(tag, ['sug'])
        tags.update_tag_implications.assert_called_once_with(tag, ['imp'])


def test_update_tag_category_name(tag_factory):
    with patch('szurubooru.func.tag_categories.get_category_by_name'):
        tag_categories.get_category_by_name.return_value = 'mocked'
        tag = tag_factory()
        tags.update_tag_category_name(tag, 'cat')
        assert tag_categories.get_category_by_name.called_once_with('cat')
        assert tag.category == 'mocked'


def test_update_tag_names_to_empty(tag_factory):
    tag = tag_factory()
    with pytest.raises(tags.InvalidTagNameError):
        tags.update_tag_names(tag, [])


def test_update_tag_names_with_invalid_name(config_injector, tag_factory):
    config_injector({'tag_name_regex': '^[a-z]*$'})
    tag = tag_factory()
    with pytest.raises(tags.InvalidTagNameError):
        tags.update_tag_names(tag, ['0'])


def test_update_tag_names_with_too_long_string(config_injector, tag_factory):
    config_injector({'tag_name_regex': '^[a-z]*$'})
    tag = tag_factory()
    with pytest.raises(tags.InvalidTagNameError):
        tags.update_tag_names(tag, ['a' * 300])


def test_update_tag_names_with_duplicate_names(config_injector, tag_factory):
    config_injector({'tag_name_regex': '^[a-z]*$'})
    tag = tag_factory()
    tags.update_tag_names(tag, ['a', 'A'])
    assert [tag_name.name for tag_name in tag.names] == ['a']


def test_update_tag_names_trying_to_use_taken_name(
        config_injector, tag_factory):
    config_injector({'tag_name_regex': '^[a-zA-Z]*$'})
    existing_tag = tag_factory(names=['a'])
    db.session.add(existing_tag)
    tag = tag_factory()
    db.session.add(tag)
    with pytest.raises(tags.TagAlreadyExistsError):
        tags.update_tag_names(tag, ['a'])
    with pytest.raises(tags.TagAlreadyExistsError):
        tags.update_tag_names(tag, ['A'])


def test_update_tag_names_reusing_own_name(config_injector, tag_factory):
    config_injector({'tag_name_regex': '^[a-zA-Z]*$'})
    for name in list('aA'):
        tag = tag_factory(names=['a'])
        db.session.add(tag)
        db.session.flush()
        tags.update_tag_names(tag, [name])
        assert [tag_name.name for tag_name in tag.names] == [name]
        db.session.rollback()


@pytest.mark.parametrize('attempt', ['name', 'NAME', 'alias', 'ALIAS'])
def test_update_tag_suggestions_with_itself(attempt, tag_factory):
    tag = tag_factory(names=['name', 'ALIAS'])
    with pytest.raises(tags.InvalidTagRelationError):
        tags.update_tag_suggestions(tag, [attempt])


def test_update_tag_suggestions(tag_factory):
    tag = tag_factory(names=['name', 'ALIAS'])
    with patch('szurubooru.func.tags.get_tags_by_names'):
        tags.get_tags_by_names.return_value = ['returned tags']
        tags.update_tag_suggestions(tag, ['test'])
        assert tag.suggestions == ['returned tags']


@pytest.mark.parametrize('attempt', ['name', 'NAME', 'alias', 'ALIAS'])
def test_update_tag_implications_with_itself(attempt, tag_factory):
    tag = tag_factory(names=['name', 'ALIAS'])
    with pytest.raises(tags.InvalidTagRelationError):
        tags.update_tag_implications(tag, [attempt])


def test_update_tag_implications(tag_factory):
    tag = tag_factory(names=['name', 'ALIAS'])
    with patch('szurubooru.func.tags.get_tags_by_names'):
        tags.get_tags_by_names.return_value = ['returned tags']
        tags.update_tag_implications(tag, ['test'])
        assert tag.implications == ['returned tags']


def test_update_tag_description(tag_factory):
    tag = tag_factory()
    tags.update_tag_description(tag, 'test')
    assert tag.description == 'test'
