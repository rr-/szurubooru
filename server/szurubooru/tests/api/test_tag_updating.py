import datetime
import os
import pytest
from szurubooru import api, config, db, errors
from szurubooru.util import misc, tags

def get_tag(session, name):
    return session.query(db.Tag) \
        .join(db.TagName) \
        .filter(db.TagName.name==name) \
        .first()

def assert_relations(relations, expected_tag_names):
    actual_names = [rel.names[0].name for rel in relations]
    assert actual_names == expected_tag_names

@pytest.fixture
def test_ctx(
        tmpdir,
        session,
        config_injector,
        context_factory,
        user_factory,
        tag_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'tag_categories': ['meta', 'character', 'copyright'],
        'tag_name_regex': '^[^!]*$',
        'ranks': ['anonymous', 'regular_user'],
        'privileges': {
            'tags:edit:names': 'regular_user',
            'tags:edit:category': 'regular_user',
            'tags:edit:suggestions': 'regular_user',
            'tags:edit:implications': 'regular_user',
        },
    })
    ret = misc.dotdict()
    ret.session = session
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.api = api.TagDetailApi()
    return ret

def test_simple_updating(test_ctx, fake_datetime):
    fake_datetime(datetime.datetime(1997, 12, 1))
    tag = test_ctx.tag_factory(names=['tag1', 'tag2'], category='meta')
    test_ctx.session.add(tag)
    test_ctx.session.commit()
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={
                'names': ['tag3'],
                'category': 'character',
            },
            user=test_ctx.user_factory(rank='regular_user')),
        'tag1')
    assert result == {
        'tag': {
            'names': ['tag3'],
            'category': 'character',
            'suggestions': [],
            'implications': [],
            'creationTime': datetime.datetime(1996, 1, 1),
            'lastEditTime': datetime.datetime(1997, 12, 1),
        }
    }
    assert get_tag(test_ctx.session, 'tag1') is None
    assert get_tag(test_ctx.session, 'tag2') is None
    tag = get_tag(test_ctx.session, 'tag3')
    assert tag is not None
    assert [tag_name.name for tag_name in tag.names] == ['tag3']
    assert tag.category == 'character'
    assert tag.suggestions == []
    assert tag.implications == []
    assert os.path.exists(os.path.join(config.config['data_dir'], 'tags.json'))

def test_trying_to_update_non_existing_tag(test_ctx):
    with pytest.raises(tags.TagNotFoundError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'names': ['dummy']},
                user=test_ctx.user_factory(rank='regular_user')),
            'tag1')

@pytest.mark.parametrize('dup_name', ['tag1', 'TAG1'])
def test_reusing_own_name(test_ctx, dup_name):
    test_ctx.session.add(
        test_ctx.tag_factory(names=['tag1', 'tag2'], category='meta'))
    test_ctx.session.commit()
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={'names': [dup_name, 'tag3']},
            user=test_ctx.user_factory(rank='regular_user')),
        'tag1')
    assert result['tag']['names'] == ['tag1', 'tag3']
    assert get_tag(test_ctx.session, 'tag2') is None
    tag1 = get_tag(test_ctx.session, 'tag1')
    tag2 = get_tag(test_ctx.session, 'tag3')
    assert tag1.tag_id == tag2.tag_id
    assert [name.name for name in tag1.names] == ['tag1', 'tag3']

def test_duplicating_names(test_ctx):
    test_ctx.session.add(
        test_ctx.tag_factory(names=['tag1', 'tag2'], category='meta'))
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={'names': ['tag3', 'TAG3']},
            user=test_ctx.user_factory(rank='regular_user')),
        'tag1')
    assert result['tag']['names'] == ['tag3']
    assert get_tag(test_ctx.session, 'tag1') is None
    assert get_tag(test_ctx.session, 'tag2') is None
    tag = get_tag(test_ctx.session, 'tag3')
    assert tag is not None
    assert [tag_name.name for tag_name in tag.names] == ['tag3']

@pytest.mark.parametrize('input', [
    {'names': []},
    {'names': ['!']},
    {'names': ['x' * 65]},
])
def test_trying_to_set_invalid_name(test_ctx, input):
    test_ctx.session.add(test_ctx.tag_factory(names=['tag1'], category='meta'))
    test_ctx.session.commit()
    with pytest.raises(tags.InvalidNameError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input=input,
                user=test_ctx.user_factory(rank='regular_user')),
            'tag1')

@pytest.mark.parametrize('dup_name', ['tag1', 'TAG1', 'tag2', 'TAG2'])
def test_trying_to_use_existing_name(test_ctx, dup_name):
    test_ctx.session.add_all([
        test_ctx.tag_factory(names=['tag1', 'tag2'], category='meta'),
        test_ctx.tag_factory(names=['tag3', 'tag4'], category='meta')])
    test_ctx.session.commit()
    with pytest.raises(tags.TagAlreadyExistsError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'names': [dup_name]},
                user=test_ctx.user_factory(rank='regular_user')),
            'tag3')

def test_trying_to_update_tag_with_invalid_category(test_ctx):
    test_ctx.session.add(test_ctx.tag_factory(names=['tag1'], category='meta'))
    test_ctx.session.commit()
    with pytest.raises(tags.InvalidCategoryError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={
                    'names': ['ok'],
                    'category': 'invalid',
                    'suggestions': [],
                    'implications': [],
                },
                user=test_ctx.user_factory(rank='regular_user')),
            'tag1')

@pytest.mark.parametrize('input,expected_suggestions,expected_implications', [
    # new relations
    ({
        'suggestions': ['sug1', 'sug2'],
        'implications': ['imp1', 'imp2'],
    }, ['sug1', 'sug2'], ['imp1', 'imp2']),
    # overlapping relations
    ({
        'suggestions': ['sug', 'shared'],
        'implications': ['shared', 'imp'],
    }, ['sug', 'shared'], ['shared', 'imp']),
    # duplicate relations
    ({
        'suggestions': ['sug', 'SUG'],
        'implications': ['imp', 'IMP'],
    }, ['sug'], ['imp']),
    # overlapping duplicate relations
    ({
        'suggestions': ['shared1', 'shared2'],
        'implications': ['SHARED1', 'SHARED2'],
    }, ['shared1', 'shared2'], ['shared1', 'shared2']),
])
def test_updating_new_suggestions_and_implications(
        test_ctx, input, expected_suggestions, expected_implications):
    test_ctx.session.add(test_ctx.tag_factory(names=['main'], category='meta'))
    test_ctx.session.commit()
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input=input, user=test_ctx.user_factory(rank='regular_user')),
        'main')
    assert result['tag']['suggestions'] == expected_suggestions
    assert result['tag']['implications'] == expected_implications
    tag = get_tag(test_ctx.session, 'main')
    assert_relations(tag.suggestions, expected_suggestions)
    assert_relations(tag.implications, expected_implications)
    for name in ['main'] + expected_suggestions + expected_implications:
        assert get_tag(test_ctx.session, name) is not None

def test_reusing_suggestions_and_implications(test_ctx):
    test_ctx.session.add_all([
        test_ctx.tag_factory(names=['tag1', 'tag2'], category='meta'),
        test_ctx.tag_factory(names=['tag3'], category='meta'),
        test_ctx.tag_factory(names=['tag4'], category='meta'),
    ])
    test_ctx.session.commit()
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={
                'names': ['new'],
                'category': 'meta',
                'suggestions': ['TAG2'],
                'implications': ['tag1'],
            },
            user=test_ctx.user_factory(rank='regular_user')),
        'tag4')
    # NOTE: it should export only the first name
    assert result['tag']['suggestions'] == ['tag1']
    assert result['tag']['implications'] == ['tag1']
    tag = get_tag(test_ctx.session, 'new')
    assert_relations(tag.suggestions, ['tag1'])
    assert_relations(tag.implications, ['tag1'])

@pytest.mark.parametrize('input', [
    {'names': ['ok'], 'suggestions': ['ok2', '!']},
    {'names': ['ok'], 'implications': ['ok2', '!']},
])
def test_trying_to_update_tag_with_invalid_relation(test_ctx, input):
    test_ctx.session.add(test_ctx.tag_factory(names=['tag'], category='meta'))
    test_ctx.session.commit()
    with pytest.raises(tags.InvalidNameError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input=input, user=test_ctx.user_factory(rank='regular_user')),
            'tag')
    test_ctx.session.rollback()
    assert get_tag(test_ctx.session, 'tag') is not None
    assert get_tag(test_ctx.session, '!') is None
    assert get_tag(test_ctx.session, 'ok') is None
    assert get_tag(test_ctx.session, 'ok2') is None

@pytest.mark.parametrize('input', [
    {
        'names': ['tag1'],
        'category': 'meta',
        'suggestions': ['tag1'],
        'implications': [],
    },
    {
        'names': ['tag1'],
        'category': 'meta',
        'suggestions': [],
        'implications': ['tag1'],
    }
])
def test_tag_trying_to_relate_to_itself(test_ctx, input):
    test_ctx.session.add(test_ctx.tag_factory(names=['tag1'], category='meta'))
    test_ctx.session.commit()
    with pytest.raises(tags.RelationError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input=input, user=test_ctx.user_factory(rank='regular_user')),
            'tag1')

@pytest.mark.parametrize('input', [
    {'names': 'whatever'},
    {'category': 'whatever'},
    {'suggestions': ['whatever']},
    {'implications': ['whatever']},
])
def test_trying_to_update_tag_without_privileges(test_ctx, input):
    test_ctx.session.add(test_ctx.tag_factory(names=['tag'], category='meta'))
    test_ctx.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input=input,
                user=test_ctx.user_factory(rank='anonymous')),
            'tag')
