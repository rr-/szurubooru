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
