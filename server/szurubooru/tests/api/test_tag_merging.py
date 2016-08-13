import datetime
import os
import pytest
from szurubooru import api, config, db, errors
from szurubooru.func import util, tags

@pytest.fixture
def test_ctx(
        tmpdir,
        config_injector,
        context_factory,
        user_factory,
        tag_factory,
        tag_category_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'privileges': {
            'tags:merge': db.User.RANK_REGULAR,
        },
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.tag_category_factory = tag_category_factory
    ret.api = api.TagMergeApi()
    return ret

def test_merging_without_usages(test_ctx, fake_datetime):
    category = test_ctx.tag_category_factory(name='meta')
    source_tag = test_ctx.tag_factory(names=['source'])
    target_tag = test_ctx.tag_factory(names=['target'], category=category)
    db.session.add_all([source_tag, target_tag])
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'removeVersion': 1,
                    'mergeToVersion': 1,
                    'remove': 'source',
                    'mergeTo': 'target',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert 'snapshots' in result
    del result['snapshots']
    assert result == {
        'names': ['target'],
        'category': 'meta',
        'description': None,
        'suggestions': [],
        'implications': [],
        'creationTime': datetime.datetime(1996, 1, 1),
        'lastEditTime': None,
        'usages': 0,
        'version': 2,
    }
    assert tags.try_get_tag_by_name('source') is None
    tag = tags.get_tag_by_name('target')
    assert tag is not None
    assert os.path.exists(os.path.join(config.config['data_dir'], 'tags.json'))

def test_merging_with_usages(test_ctx, fake_datetime, post_factory):
    source_tag = test_ctx.tag_factory(names=['source'])
    target_tag = test_ctx.tag_factory(names=['target'])
    db.session.add_all([source_tag, target_tag])
    db.session.flush()
    assert source_tag.post_count == 0
    assert target_tag.post_count == 0
    post = post_factory()
    post.tags = [source_tag]
    db.session.add(post)
    db.session.commit()
    assert source_tag.post_count == 1
    assert target_tag.post_count == 0
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'removeVersion': 1,
                    'mergeToVersion': 1,
                    'remove': 'source',
                    'mergeTo': 'target',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert tags.try_get_tag_by_name('source') is None
    assert tags.get_tag_by_name('target').post_count == 1

def test_merging_when_related(test_ctx, fake_datetime):
    source_tag = test_ctx.tag_factory(names=['source'])
    target_tag = test_ctx.tag_factory(names=['target'])
    db.session.add_all([source_tag, target_tag])
    db.session.flush()
    referring_tag = test_ctx.tag_factory(names=['parent'])
    referring_tag.suggestions = [source_tag]
    referring_tag.implications = [source_tag]
    db.session.add(referring_tag)
    db.session.commit()
    assert tags.try_get_tag_by_name('parent').implications != []
    assert tags.try_get_tag_by_name('parent').suggestions != []
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'removeVersion': 1,
                    'mergeToVersion': 1,
                    'remove': 'source',
                    'mergeTo': 'target',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert tags.try_get_tag_by_name('source') is None
    assert tags.try_get_tag_by_name('parent').implications == []
    assert tags.try_get_tag_by_name('parent').suggestions == []

def test_merging_when_target_exists(test_ctx, fake_datetime, post_factory):
    source_tag = test_ctx.tag_factory(names=['source'])
    target_tag = test_ctx.tag_factory(names=['target'])
    db.session.add_all([source_tag, target_tag])
    db.session.flush()
    post1 = post_factory()
    post1.tags = [source_tag, target_tag]
    db.session.add_all([post1])
    db.session.commit()
    assert source_tag.post_count == 1
    assert target_tag.post_count == 1
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'removeVersion': 1,
                    'mergeToVersion': 1,
                    'remove': 'source',
                    'mergeTo': 'target',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert tags.try_get_tag_by_name('source') is None
    assert tags.get_tag_by_name('target').post_count == 1

@pytest.mark.parametrize('input,expected_exception', [
    ({'remove': None}, tags.TagNotFoundError),
    ({'remove': ''}, tags.TagNotFoundError),
    ({'remove': []}, tags.TagNotFoundError),
    ({'mergeTo': None}, tags.TagNotFoundError),
    ({'mergeTo': ''}, tags.TagNotFoundError),
    ({'mergeTo': []}, tags.TagNotFoundError),
])
def test_trying_to_pass_invalid_input(test_ctx, input, expected_exception):
    source_tag = test_ctx.tag_factory(names=['source'])
    target_tag = test_ctx.tag_factory(names=['target'])
    db.session.add_all([source_tag, target_tag])
    db.session.commit()
    real_input = {
        'removeVersion': 1,
        'mergeToVersion': 1,
        'remove': 'source',
        'mergeTo': 'target',
    }
    for key, value in input.items():
        real_input[key] = value
    with pytest.raises(expected_exception):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=real_input,
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

@pytest.mark.parametrize(
    'field', ['remove', 'mergeTo', 'removeVersion', 'mergeToVersion'])
def test_trying_to_omit_mandatory_field(test_ctx, field):
    db.session.add_all([
        test_ctx.tag_factory(names=['source']),
        test_ctx.tag_factory(names=['target']),
    ])
    db.session.commit()
    input = {
        'removeVersion': 1,
        'mergeToVersion': 1,
        'remove': 'source',
        'mergeTo': 'target',
    }
    del input[field]
    with pytest.raises(errors.ValidationError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=input,
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_merge_non_existing(test_ctx):
    db.session.add(test_ctx.tag_factory(names=['good']))
    db.session.commit()
    with pytest.raises(tags.TagNotFoundError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={'remove': 'good', 'mergeTo': 'bad'},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    with pytest.raises(tags.TagNotFoundError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={'remove': 'bad', 'mergeTo': 'good'},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_merge_to_itself(test_ctx):
    db.session.add(test_ctx.tag_factory(names=['good']))
    db.session.commit()
    with pytest.raises(tags.InvalidTagRelationError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'removeVersion': 1,
                    'mergeToVersion': 1,
                    'remove': 'good',
                    'mergeTo': 'good'},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

@pytest.mark.parametrize('input', [
    {'names': 'whatever'},
    {'category': 'whatever'},
    {'suggestions': ['whatever']},
    {'implications': ['whatever']},
])
def test_trying_to_merge_without_privileges(test_ctx, input):
    db.session.add_all([
        test_ctx.tag_factory(names=['source']),
        test_ctx.tag_factory(names=['target']),
    ])
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'removeVersion': 1,
                    'mergeToVersion': 1,
                    'remove': 'source',
                    'mergeTo': 'target',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)))
