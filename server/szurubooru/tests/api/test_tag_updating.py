import datetime
import os
import pytest
from szurubooru import api, config, db, errors
from szurubooru.func import util, tags, tag_categories

def assert_relations(relations, expected_tag_names):
    actual_names = [rel.names[0].name for rel in relations]
    assert actual_names == expected_tag_names

@pytest.fixture
def test_ctx(
        tmpdir, config_injector, context_factory, user_factory, tag_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'tag_name_regex': '^[^!]*$',
        'tag_category_name_regex': '^[^!]*$',
        'privileges': {
            'tags:create': db.User.RANK_REGULAR,
            'tags:edit:names': db.User.RANK_REGULAR,
            'tags:edit:category': db.User.RANK_REGULAR,
            'tags:edit:suggestions': db.User.RANK_REGULAR,
            'tags:edit:implications': db.User.RANK_REGULAR,
        },
    })
    db.session.add_all([
        db.TagCategory(name) for name in ['meta', 'character', 'copyright']])
    db.session.flush()
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.api = api.TagDetailApi()
    return ret

def test_simple_updating(test_ctx, fake_datetime):
    tag = test_ctx.tag_factory(names=['tag1', 'tag2'], category_name='meta')
    db.session.add(tag)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(
                input={
                    'names': ['tag3'],
                    'category': 'character',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            'tag1')
    assert result['tag'] == {
        'names': ['tag3'],
        'category': 'character',
        'suggestions': [],
        'implications': [],
        'creationTime': datetime.datetime(1996, 1, 1),
        'lastEditTime': datetime.datetime(1997, 12, 1),
        'usages': 0,
    }
    assert len(result['snapshots']) == 1
    assert tags.try_get_tag_by_name('tag1') is None
    assert tags.try_get_tag_by_name('tag2') is None
    tag = tags.get_tag_by_name('tag3')
    assert tag is not None
    assert [tag_name.name for tag_name in tag.names] == ['tag3']
    assert tag.category.name == 'character'
    assert tag.suggestions == []
    assert tag.implications == []
    assert os.path.exists(os.path.join(config.config['data_dir'], 'tags.json'))

@pytest.mark.parametrize('input,expected_exception', [
    ({'names': None}, tags.InvalidTagNameError),
    ({'names': []}, tags.InvalidTagNameError),
    ({'names': [None]}, tags.InvalidTagNameError),
    ({'names': ['']}, tags.InvalidTagNameError),
    ({'names': ['!bad']}, tags.InvalidTagNameError),
    ({'names': ['x' * 65]}, tags.InvalidTagNameError),
    ({'category': None}, tag_categories.InvalidTagCategoryNameError),
    ({'category': ''}, tag_categories.InvalidTagCategoryNameError),
    ({'category': '!bad'}, tag_categories.InvalidTagCategoryNameError),
    ({'suggestions': ['good', '!bad']}, tags.InvalidTagNameError),
    ({'implications': ['good', '!bad']}, tags.InvalidTagNameError),
])
def test_trying_to_pass_invalid_input(test_ctx, input, expected_exception):
    db.session.add(test_ctx.tag_factory(names=['tag1'], category_name='meta'))
    db.session.commit()
    with pytest.raises(expected_exception):
        test_ctx.api.put(
            test_ctx.context_factory(
                input=input,
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            'tag1')

@pytest.mark.parametrize(
    'field', ['names', 'category', 'implications', 'suggestions'])
def test_omitting_optional_field(test_ctx, field):
    db.session.add(test_ctx.tag_factory(names=['tag'], category_name='meta'))
    db.session.commit()
    input = {
        'names': ['tag1', 'tag2'],
        'category': 'meta',
        'suggestions': [],
        'implications': [],
    }
    del input[field]
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input=input,
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
        'tag')
    assert result is not None

def test_trying_to_update_non_existing(test_ctx):
    with pytest.raises(tags.TagNotFoundError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'names': ['dummy']},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            'tag1')

@pytest.mark.parametrize('dup_name', ['tag1', 'TAG1'])
def test_reusing_own_name(test_ctx, dup_name):
    db.session.add(
        test_ctx.tag_factory(names=['tag1', 'tag2'], category_name='meta'))
    db.session.commit()
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={'names': [dup_name, 'tag3']},
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
        'tag1')
    assert result['tag']['names'] == ['tag1', 'tag3']
    assert tags.try_get_tag_by_name('tag2') is None
    tag1 = tags.get_tag_by_name('tag1')
    tag2 = tags.get_tag_by_name('tag3')
    assert tag1.tag_id == tag2.tag_id
    assert [name.name for name in tag1.names] == ['tag1', 'tag3']

def test_duplicating_names(test_ctx):
    db.session.add(
        test_ctx.tag_factory(names=['tag1', 'tag2'], category_name='meta'))
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={'names': ['tag3', 'TAG3']},
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
        'tag1')
    assert result['tag']['names'] == ['tag3']
    assert tags.try_get_tag_by_name('tag1') is None
    assert tags.try_get_tag_by_name('tag2') is None
    tag = tags.get_tag_by_name('tag3')
    assert tag is not None
    assert [tag_name.name for tag_name in tag.names] == ['tag3']

@pytest.mark.parametrize('dup_name', ['tag1', 'TAG1', 'tag2', 'TAG2'])
def test_trying_to_use_existing_name(test_ctx, dup_name):
    db.session.add_all([
        test_ctx.tag_factory(names=['tag1', 'tag2'], category_name='meta'),
        test_ctx.tag_factory(names=['tag3', 'tag4'], category_name='meta')])
    db.session.commit()
    with pytest.raises(tags.TagAlreadyExistsError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={'names': [dup_name]},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            'tag3')

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
    db.session.add(
        test_ctx.tag_factory(names=['main'], category_name='meta'))
    db.session.commit()
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input=input, user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
        'main')
    assert result['tag']['suggestions'] == expected_suggestions
    assert result['tag']['implications'] == expected_implications
    tag = tags.get_tag_by_name('main')
    assert_relations(tag.suggestions, expected_suggestions)
    assert_relations(tag.implications, expected_implications)
    for name in ['main'] + expected_suggestions + expected_implications:
        assert tags.try_get_tag_by_name(name) is not None

def test_reusing_suggestions_and_implications(test_ctx):
    db.session.add_all([
        test_ctx.tag_factory(names=['tag1', 'tag2'], category_name='meta'),
        test_ctx.tag_factory(names=['tag3'], category_name='meta'),
        test_ctx.tag_factory(names=['tag4'], category_name='meta'),
    ])
    db.session.commit()
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={
                'names': ['new'],
                'category': 'meta',
                'suggestions': ['TAG2'],
                'implications': ['tag1'],
            },
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
        'tag4')
    # NOTE: it should export only the first name
    assert result['tag']['suggestions'] == ['tag1']
    assert result['tag']['implications'] == ['tag1']
    tag = tags.get_tag_by_name('new')
    assert_relations(tag.suggestions, ['tag1'])
    assert_relations(tag.implications, ['tag1'])

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
def test_trying_to_relate_tag_to_itself(test_ctx, input):
    db.session.add(test_ctx.tag_factory(names=['tag1'], category_name='meta'))
    db.session.commit()
    with pytest.raises(tags.InvalidTagRelationError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input=input, user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            'tag1')

@pytest.mark.parametrize('input', [
    {'names': 'whatever'},
    {'category': 'whatever'},
    {'suggestions': ['whatever']},
    {'implications': ['whatever']},
])
def test_trying_to_update_without_privileges(test_ctx, input):
    db.session.add(test_ctx.tag_factory(names=['tag'], category_name='meta'))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input=input,
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)),
            'tag')
