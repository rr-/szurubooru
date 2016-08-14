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
        'privileges': {
            'tags:create': db.User.RANK_REGULAR,
            'tags:edit:names': db.User.RANK_REGULAR,
            'tags:edit:category': db.User.RANK_REGULAR,
            'tags:edit:description': db.User.RANK_REGULAR,
            'tags:edit:suggestions': db.User.RANK_REGULAR,
            'tags:edit:implications': db.User.RANK_REGULAR,
        },
    })
    db.session.add_all([
        db.TagCategory(name) for name in ['meta', 'character', 'copyright']])
    db.session.commit()
    cache.purge()
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.api = api.TagDetailApi()
    return ret

def test_simple_updating(test_ctx, fake_datetime):
    tag = test_ctx.tag_factory(names=['tag1', 'tag2'])
    db.session.add(tag)
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.put(
            test_ctx.context_factory(
                input={
                    'version': 1,
                    'names': ['tag3'],
                    'category': 'character',
                    'description': 'desc',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            'tag1')
    assert len(result['snapshots']) == 1
    del result['snapshots']
    assert result == {
        'names': ['tag3'],
        'category': 'character',
        'description': 'desc',
        'suggestions': [],
        'implications': [],
        'creationTime': datetime.datetime(1996, 1, 1),
        'lastEditTime': datetime.datetime(1997, 12, 1),
        'usages': 0,
        'version': 2,
    }
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
    ({'category': None}, tag_categories.TagCategoryNotFoundError),
    ({'category': ''}, tag_categories.TagCategoryNotFoundError),
    ({'category': '!bad'}, tag_categories.TagCategoryNotFoundError),
    ({'suggestions': ['good', '!bad']}, tags.InvalidTagNameError),
    ({'implications': ['good', '!bad']}, tags.InvalidTagNameError),
])
def test_trying_to_pass_invalid_input(test_ctx, input, expected_exception):
    db.session.add(test_ctx.tag_factory(names=['tag1']))
    db.session.commit()
    with pytest.raises(expected_exception):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={**input, **{'version': 1}},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            'tag1')

@pytest.mark.parametrize(
    'field', ['names', 'category', 'description', 'implications', 'suggestions'])
def test_omitting_optional_field(test_ctx, field):
    db.session.add(test_ctx.tag_factory(names=['tag']))
    db.session.commit()
    input = {
        'names': ['tag1', 'tag2'],
        'category': 'meta',
        'description': 'desc',
        'suggestions': [],
        'implications': [],
    }
    del input[field]
    result = test_ctx.api.put(
        test_ctx.context_factory(
            input={**input, **{'version': 1}},
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

@pytest.mark.parametrize('input', [
    {'names': 'whatever'},
    {'category': 'whatever'},
    {'suggestions': ['whatever']},
    {'implications': ['whatever']},
])
def test_trying_to_update_without_privileges(test_ctx, input):
    db.session.add(test_ctx.tag_factory(names=['tag']))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.put(
            test_ctx.context_factory(
                input={**input, **{'version': 1}},
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)),
            'tag')
