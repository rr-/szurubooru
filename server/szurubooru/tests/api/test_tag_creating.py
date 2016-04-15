import pytest
from datetime import datetime
from szurubooru import api, db, errors
from szurubooru.util import auth

@pytest.fixture
def tag_config(config_injector):
    config_injector({
        'tag_categories': ['meta', 'character', 'copyright'],
        'tag_name_regex': '^[^!]*$',
        'ranks': ['regular_user'],
        'privileges': {'tags:create': 'regular_user'},
    })

@pytest.fixture
def tag_list_api(tag_config):
    return api.TagListApi()

def get_tag(session, name):
    return session.query(db.Tag) \
        .join(db.TagName) \
        .filter(db.TagName.name==name) \
        .one()

def assert_relations(relations, expected_tag_names):
    actual_names = [rel.child_tag.names[0].name for rel in relations]
    assert actual_names == expected_tag_names

def test_creating_simple_tags(
        session, context_factory, user_factory, tag_list_api):
    result = tag_list_api.post(
        context_factory(
            input={
                'names': ['tag1', 'tag2'],
                'category': 'meta',
                'implications': [],
                'suggestions': [],
            },
            user=user_factory(rank='regular_user')))
    assert result == {
        'tag': {
            'names': ['tag1', 'tag2'],
            'category': 'meta',
            'suggestions': [],
            'implications': [],
        }
    }
    tag = get_tag(session, 'tag1')
    assert [tag_name.name for tag_name in tag.names] == ['tag1', 'tag2']
    assert tag.category == 'meta'
    #TODO: assert tag.creation_time == something
    assert tag.last_edit_time is None
    assert tag.post_count == 0
    assert_relations(tag.suggestions, [])
    assert_relations(tag.implications, [])

def test_duplicating_names(
        session, context_factory, user_factory, tag_list_api):
    result = tag_list_api.post(
        context_factory(
            input={
                'names': ['tag1', 'TAG1'],
                'category': 'meta',
                'implications': [],
                'suggestions': [],
            },
            user=user_factory(rank='regular_user')))
    assert result['tag']['names'] == ['tag1']
    assert result['tag']['category'] == 'meta'
    tag = get_tag(session, 'tag1')
    assert [tag_name.name for tag_name in tag.names] == ['tag1']

def test_trying_to_create_tag_without_names(
        session, context_factory, user_factory, tag_list_api):
    with pytest.raises(errors.ValidationError):
        tag_list_api.post(
            context_factory(
                input={
                    'names': [],
                    'category': 'meta',
                    'implications': [],
                    'suggestions': [],
                },
                user=user_factory(rank='regular_user')))

def test_trying_to_use_existing_name(
        session, context_factory, user_factory, tag_factory, tag_list_api):
    session.add(tag_factory(names=['used1'], category='meta'))
    session.add(tag_factory(names=['used2'], category='meta'))
    session.commit()
    with pytest.raises(errors.ValidationError):
        tag_list_api.post(
            context_factory(
                input={
                    'names': ['used1', 'unused'],
                    'category': 'meta',
                    'implications': [],
                    'suggestions': [],
                },
                user=user_factory(rank='regular_user')))
    with pytest.raises(errors.ValidationError):
        tag_list_api.post(
            context_factory(
                input={
                    'names': ['USED2', 'unused'],
                    'category': 'meta',
                    'implications': [],
                    'suggestions': [],
                },
                user=user_factory(rank='regular_user')))

def test_trying_to_create_tag_with_invalid_name(
        session, context_factory, user_factory, tag_list_api):
    with pytest.raises(errors.ValidationError):
        tag_list_api.post(
            context_factory(
                input={
                    'names': ['!'],
                    'category': 'meta',
                    'implications': [],
                    'suggestions': [],
                },
                user=user_factory(rank='regular_user')))
    with pytest.raises(errors.ValidationError):
        tag_list_api.post(
            context_factory(
                input={
                    'names': ['ok'],
                    'category': 'meta',
                    'implications': ['!'],
                    'suggestions': [],
                },
                user=user_factory(rank='regular_user')))
    with pytest.raises(errors.ValidationError):
        tag_list_api.post(
            context_factory(
                input={
                    'names': ['ok'],
                    'category': 'meta',
                    'implications': [],
                    'suggestions': ['!'],
                },
                user=user_factory(rank='regular_user')))

def test_trying_to_create_tag_with_invalid_category(
        session, context_factory, user_factory, tag_list_api):
    with pytest.raises(errors.ValidationError):
        tag_list_api.post(
            context_factory(
                input={
                    'names': ['ok'],
                    'category': 'invalid',
                    'implications': [],
                    'suggestions': [],
                },
                user=user_factory(rank='regular_user')))

def test_creating_new_suggestions_and_implications(
        session, context_factory, user_factory, tag_list_api):
    result = tag_list_api.post(
        context_factory(
            input={
                'names': ['tag1'],
                'category': 'meta',
                'implications': ['tag2', 'tag3'],
                'suggestions': ['tag4', 'tag5'],
            },
            user=user_factory(rank='regular_user')))
    assert result['tag']['implications'] == ['tag2', 'tag3']
    assert result['tag']['suggestions'] == ['tag4', 'tag5']
    tag = get_tag(session, 'tag1')
    assert_relations(tag.implications, ['tag2', 'tag3'])
    assert_relations(tag.suggestions, ['tag4', 'tag5'])

def test_duplicating_suggestions_and_implications(
        session, context_factory, user_factory, tag_list_api):
    result = tag_list_api.post(
        context_factory(
            input={
                'names': ['tag1'],
                'category': 'meta',
                'implications': ['tag2', 'TAG2'],
                'suggestions': ['tag3', 'TAG3'],
            },
            user=user_factory(rank='regular_user')))
    assert result['tag']['implications'] == ['tag2']
    assert result['tag']['suggestions'] == ['tag3']
    tag = get_tag(session, 'tag1')
    assert_relations(tag.implications, ['tag2'])
    assert_relations(tag.suggestions, ['tag3'])

def test_reusing_suggestions_and_implications(
        session,
        context_factory,
        user_factory,
        tag_factory,
        tag_list_api):
    session.add(tag_factory(names=['tag1', 'tag2'], category='meta'))
    session.add(tag_factory(names=['tag3'], category='meta'))
    session.commit()
    result = tag_list_api.post(
        context_factory(
            input={
                'names': ['new'],
                'category': 'meta',
                'implications': ['tag1'],
                'suggestions': ['TAG2'],
            },
            user=user_factory(rank='regular_user')))
    assert result['tag']['implications'] == ['tag1']
    # NOTE: it should export only the first name
    assert result['tag']['suggestions'] == ['tag1']
    tag = get_tag(session, 'new')
    assert_relations(tag.implications, ['tag1'])
    assert_relations(tag.suggestions, ['tag1'])

def test_tag_trying_to_imply_or_suggest_itself(
        session, context_factory, user_factory, tag_list_api):
    with pytest.raises(errors.ValidationError):
        tag_list_api.post(
            context_factory(
                input={
                    'names': ['tag1'],
                    'category': 'meta',
                    'implications': ['tag1'],
                    'suggestions': [],
                },
                user=user_factory(rank='regular_user')))
    with pytest.raises(errors.ValidationError):
        tag_list_api.post(
            context_factory(
                input={
                    'names': ['tag1'],
                    'category': 'meta',
                    'implications': [],
                    'suggestions': ['tag1'],
                },
                user=user_factory(rank='regular_user')))

# TODO: test bad privileges
# TODO: test max length
