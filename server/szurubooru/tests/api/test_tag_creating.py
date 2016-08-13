import datetime
import os
import pytest
from szurubooru import api, config, db, errors
from szurubooru.func import util, tags, tag_categories, cache

def assert_relations(relations, expected_tag_names):
    actual_names = sorted([rel.names[0].name for rel in relations])
    assert actual_names == sorted(expected_tag_names)

@pytest.fixture
def test_ctx(
        tmpdir, config_injector, context_factory, user_factory, tag_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'tag_name_regex': '^[^!]*$',
        'tag_category_name_regex': '^[^!]*$',
        'privileges': {'tags:create': db.User.RANK_REGULAR},
    })
    db.session.add_all([
        db.TagCategory(name) for name in ['meta', 'character', 'copyright']])
    db.session.flush()
    cache.purge()
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.api = api.TagListApi()
    return ret

def test_creating_simple_tags(test_ctx, fake_datetime):
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'names': ['tag1', 'tag2'],
                    'category': 'meta',
                    'description': 'desc',
                    'suggestions': [],
                    'implications': [],
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert len(result['snapshots']) == 1
    del result['snapshots']
    assert result == {
        'names': ['tag1', 'tag2'],
        'category': 'meta',
        'description': 'desc',
        'suggestions': [],
        'implications': [],
        'creationTime': datetime.datetime(1997, 12, 1),
        'lastEditTime': None,
        'usages': 0,
        'version': 1,
    }
    tag = tags.get_tag_by_name('tag1')
    assert [tag_name.name for tag_name in tag.names] == ['tag1', 'tag2']
    assert tag.category.name == 'meta'
    assert tag.last_edit_time is None
    assert tag.post_count == 0
    assert_relations(tag.suggestions, [])
    assert_relations(tag.implications, [])
    assert os.path.exists(os.path.join(config.config['data_dir'], 'tags.json'))

@pytest.mark.parametrize('input,expected_exception', [
    ({'names': None}, tags.InvalidTagNameError),
    ({'names': []}, tags.InvalidTagNameError),
    ({'names': [None]}, tags.InvalidTagNameError),
    ({'names': ['']}, tags.InvalidTagNameError),
    ({'names': ['!bad']}, tags.InvalidTagNameError),
    ({'names': ['x' * 65]}, tags.InvalidTagNameError),
    ({'category': None}, tag_categories.TagCategoryNotFoundError),
    ({'category': ''}, tag_categories.TagCategoryNotFoundError),
    ({'category': '!bad'}, tag_categories.TagCategoryNotFoundError),
    ({'suggestions': ['good', '!bad']}, tags.InvalidTagNameError),
    ({'implications': ['good', '!bad']}, tags.InvalidTagNameError),
])
def test_trying_to_pass_invalid_input(test_ctx, input, expected_exception):
    real_input={
        'names': ['tag1', 'tag2'],
        'category': 'meta',
        'suggestions': [],
        'implications': [],
    }
    for key, value in input.items():
        real_input[key] = value
    with pytest.raises(expected_exception):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=real_input,
                user=test_ctx.user_factory()))

@pytest.mark.parametrize('field', ['names', 'category'])
def test_trying_to_omit_mandatory_field(test_ctx, field):
    input = {
        'names': ['tag1', 'tag2'],
        'category': 'meta',
        'suggestions': [],
        'implications': [],
    }
    del input[field]
    with pytest.raises(errors.ValidationError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=input,
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

@pytest.mark.parametrize('field', ['implications', 'suggestions'])
def test_omitting_optional_field(test_ctx, field):
    input = {
        'names': ['tag1', 'tag2'],
        'category': 'meta',
        'suggestions': [],
        'implications': [],
    }
    del input[field]
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input=input,
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert result is not None

def test_duplicating_names(test_ctx):
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'names': ['tag1', 'TAG1'],
                'category': 'meta',
                'suggestions': [],
                'implications': [],
            },
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert result['names'] == ['tag1']
    assert result['category'] == 'meta'
    tag = tags.get_tag_by_name('tag1')
    assert [tag_name.name for tag_name in tag.names] == ['tag1']

def test_trying_to_use_existing_name(test_ctx):
    db.session.add_all([
        test_ctx.tag_factory(names=['used1']),
        test_ctx.tag_factory(names=['used2']),
    ])
    db.session.commit()
    with pytest.raises(tags.TagAlreadyExistsError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'names': ['used1', 'unused'],
                    'category': 'meta',
                    'suggestions': [],
                    'implications': [],
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    with pytest.raises(tags.TagAlreadyExistsError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'names': ['USED2', 'unused'],
                    'category': 'meta',
                    'suggestions': [],
                    'implications': [],
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert tags.try_get_tag_by_name('unused') is None

def test_creating_new_category(test_ctx):
    with pytest.raises(tag_categories.TagCategoryNotFoundError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'names': ['main'],
                    'category': 'new',
                    'suggestions': [],
                    'implications': [],
                }, user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

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
    }, ['shared', 'sug'], ['imp', 'shared']),
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
            input=input, user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert result['suggestions'] == expected_suggestions
    assert result['implications'] == expected_implications
    tag = tags.get_tag_by_name('main')
    assert_relations(tag.suggestions, expected_suggestions)
    assert_relations(tag.implications, expected_implications)
    for name in ['main'] + expected_suggestions + expected_implications:
        assert tags.try_get_tag_by_name(name) is not None

def test_reusing_suggestions_and_implications(test_ctx):
    db.session.add_all([
        test_ctx.tag_factory(names=['tag1', 'tag2']),
        test_ctx.tag_factory(names=['tag3']),
    ])
    db.session.commit()
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={
                'names': ['new'],
                'category': 'meta',
                'suggestions': ['TAG2'],
                'implications': ['tag1'],
            },
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    # NOTE: it should export only the first name
    assert result['suggestions'] == ['tag1']
    assert result['implications'] == ['tag1']
    tag = tags.get_tag_by_name('new')
    assert_relations(tag.suggestions, ['tag1'])
    assert_relations(tag.implications, ['tag1'])

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
    with pytest.raises(tags.TagAlreadyExistsError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=input,
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    db.session.rollback()
    assert tags.try_get_tag_by_name('tag') is None

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
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)))
