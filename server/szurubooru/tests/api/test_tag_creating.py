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
        'privileges': {'tags:create': 'regular_user'},
    })
    ret = misc.dotdict()
    ret.session = session
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.api = api.TagListApi()
    return ret

def test_creating_simple_tags(test_ctx, fake_datetime):
    fake_datetime(datetime.datetime(1997, 12, 1))
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'names': ['tag1', 'tag2'],
                'category': 'meta',
                'suggestions': [],
                'implications': [],
            },
            user=test_ctx.user_factory(rank='regular_user')))
    assert result == {
        'tag': {
            'names': ['tag1', 'tag2'],
            'category': 'meta',
            'suggestions': [],
            'implications': [],
            'creationTime': datetime.datetime(1997, 12, 1),
            'lastEditTime': None,
        }
    }
    tag = get_tag(test_ctx.session, 'tag1')
    assert [tag_name.name for tag_name in tag.names] == ['tag1', 'tag2']
    assert tag.category == 'meta'
    assert tag.last_edit_time is None
    assert tag.post_count == 0
    assert_relations(tag.suggestions, [])
    assert_relations(tag.implications, [])
    assert os.path.exists(os.path.join(config.config['data_dir'], 'tags.json'))

def test_duplicating_names(test_ctx):
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'names': ['tag1', 'TAG1'],
                'category': 'meta',
                'suggestions': [],
                'implications': [],
            },
            user=test_ctx.user_factory(rank='regular_user')))
    assert result['tag']['names'] == ['tag1']
    assert result['tag']['category'] == 'meta'
    tag = get_tag(test_ctx.session, 'tag1')
    assert [tag_name.name for tag_name in tag.names] == ['tag1']

def test_trying_to_create_tag_without_names(test_ctx):
    with pytest.raises(tags.InvalidNameError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'names': [],
                    'category': 'meta',
                    'suggestions': [],
                    'implications': [],
                },
                user=test_ctx.user_factory(rank='regular_user')))

@pytest.mark.parametrize('names', [['!'], ['x' * 65]])
def test_trying_to_create_tag_with_invalid_name(test_ctx, names):
    with pytest.raises(tags.InvalidNameError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'names': names,
                    'category': 'meta',
                    'suggestions': [],
                    'implications': [],
                },
                user=test_ctx.user_factory(rank='regular_user')))
    assert get_tag(test_ctx.session, 'ok') is None
    assert get_tag(test_ctx.session, '!') is None

def test_trying_to_use_existing_name(test_ctx):
    test_ctx.session.add_all([
        test_ctx.tag_factory(names=['used1'], category='meta'),
        test_ctx.tag_factory(names=['used2'], category='meta'),
    ])
    test_ctx.session.commit()
    with pytest.raises(tags.TagAlreadyExistsError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'names': ['used1', 'unused'],
                    'category': 'meta',
                    'suggestions': [],
                    'implications': [],
                },
                user=test_ctx.user_factory(rank='regular_user')))
    with pytest.raises(tags.TagAlreadyExistsError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'names': ['USED2', 'unused'],
                    'category': 'meta',
                    'suggestions': [],
                    'implications': [],
                },
                user=test_ctx.user_factory(rank='regular_user')))
    assert get_tag(test_ctx.session, 'unused') is None

def test_trying_to_create_tag_with_invalid_category(test_ctx):
    with pytest.raises(tags.InvalidCategoryError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'names': ['ok'],
                    'category': 'invalid',
                    'suggestions': [],
                    'implications': [],
                },
                user=test_ctx.user_factory(rank='regular_user')))
    assert get_tag(test_ctx.session, 'ok') is None

@pytest.mark.parametrize('input,expected_suggestions,expected_implications', [
    # new relations
    ({
        'names': ['main'],
        'category': 'meta',
        'suggestions': ['sug1', 'sug2'],
        'implications': ['imp1', 'imp2'],
    }, ['sug1', 'sug2'], ['imp1', 'imp2']),
    # overlapping relations
    ({
        'names': ['main'],
        'category': 'meta',
        'suggestions': ['sug', 'shared'],
        'implications': ['shared', 'imp'],
    }, ['sug', 'shared'], ['shared', 'imp']),
    # duplicate relations
    ({
        'names': ['main'],
        'category': 'meta',
        'suggestions': ['sug', 'SUG'],
        'implications': ['imp', 'IMP'],
    }, ['sug'], ['imp']),
    # overlapping duplicate relations
    ({
        'names': ['main'],
        'category': 'meta',
        'suggestions': ['shared1', 'shared2'],
        'implications': ['SHARED1', 'SHARED2'],
    }, ['shared1', 'shared2'], ['shared1', 'shared2']),
])
def test_creating_new_suggestions_and_implications(
        test_ctx, input, expected_suggestions, expected_implications):
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input=input, user=test_ctx.user_factory(rank='regular_user')))
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
    ])
    test_ctx.session.commit()
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'names': ['new'],
                'category': 'meta',
                'suggestions': ['TAG2'],
                'implications': ['tag1'],
            },
            user=test_ctx.user_factory(rank='regular_user')))
    # NOTE: it should export only the first name
    assert result['tag']['suggestions'] == ['tag1']
    assert result['tag']['implications'] == ['tag1']
    tag = get_tag(test_ctx.session, 'new')
    assert_relations(tag.suggestions, ['tag1'])
    assert_relations(tag.implications, ['tag1'])

@pytest.mark.parametrize('input', [
    {
        'names': ['ok'],
        'category': 'meta',
        'suggestions': [],
        'implications': ['ok2', '!'],
    },
    {
        'names': ['ok'],
        'category': 'meta',
        'suggestions': ['ok2', '!'],
        'implications': [],
    }
])
def test_trying_to_create_tag_with_invalid_relation(test_ctx, input):
    with pytest.raises(tags.InvalidNameError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=input, user=test_ctx.user_factory(rank='regular_user')))
    assert get_tag(test_ctx.session, 'ok') is None
    assert get_tag(test_ctx.session, 'ok2') is None
    assert get_tag(test_ctx.session, '!') is None

@pytest.mark.parametrize('input', [
    {
        'names': ['tag'],
        'category': 'meta',
        'suggestions': ['tag'],
        'implications': [],
    },
    {
        'names': ['tag'],
        'category': 'meta',
        'suggestions': [],
        'implications': ['tag'],
    }
])
def test_tag_trying_to_relate_to_itself(test_ctx, input):
    with pytest.raises(tags.RelationError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=input,
                user=test_ctx.user_factory(rank='regular_user')))
    assert get_tag(test_ctx.session, 'tag') is None

def test_trying_to_create_tag_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'names': ['tag'],
                    'category': 'meta',
                    'suggestions': ['tag'],
                    'implications': [],
                },
                user=test_ctx.user_factory(rank='anonymous')))
