import datetime
import os
import pytest
from szurubooru import api, config, db, errors
from szurubooru.func import util, tags

@pytest.fixture
def test_ctx(
        tmpdir, config_injector, context_factory, user_factory, tag_factory):
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
    ret.api = api.TagMergeApi()
    return ret

def test_merging_without_usages(test_ctx, fake_datetime):
    source_tag = test_ctx.tag_factory(names=['source'], category_name='meta')
    target_tag = test_ctx.tag_factory(names=['target'], category_name='meta')
    db.session.add_all([source_tag, target_tag])
    db.session.commit()
    with fake_datetime('1997-12-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'remove': 'source',
                    'mergeTo': 'target',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert result['tag'] == {
        'names': ['target'],
        'category': 'meta',
        'suggestions': [],
        'implications': [],
        'creationTime': datetime.datetime(1996, 1, 1),
        'lastEditTime': None,
        'usages': 0,
    }
    assert 'snapshots' in result
    assert tags.try_get_tag_by_name('source') is None
    tag = tags.get_tag_by_name('target')
    assert tag is not None
    assert os.path.exists(os.path.join(config.config['data_dir'], 'tags.json'))

def test_merging_with_usages(test_ctx, fake_datetime, post_factory):
    source_tag = test_ctx.tag_factory(names=['source'], category_name='meta')
    target_tag = test_ctx.tag_factory(names=['target'], category_name='meta')
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
    source_tag = test_ctx.tag_factory(names=['source'], category_name='meta')
    target_tag = test_ctx.tag_factory(names=['target'], category_name='meta')
    db.session.add_all([source_tag, target_tag])
    db.session.commit()
    real_input = {
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
    'field', ['remove', 'mergeTo'])
def test_trying_to_omit_mandatory_field(test_ctx, field):
    db.session.add_all([
        test_ctx.tag_factory(names=['source'], category_name='meta'),
        test_ctx.tag_factory(names=['target'], category_name='meta'),
    ])
    db.session.commit()
    input = {
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
    db.session.add(test_ctx.tag_factory(names=['good'], category_name='meta'))
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
    db.session.add(test_ctx.tag_factory(names=['good'], category_name='meta'))
    db.session.commit()
    with pytest.raises(tags.InvalidTagRelationError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={'remove': 'good', 'mergeTo': 'good'},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

@pytest.mark.parametrize('input', [
    {'names': 'whatever'},
    {'category': 'whatever'},
    {'suggestions': ['whatever']},
    {'implications': ['whatever']},
])
def test_trying_to_merge_without_privileges(test_ctx, input):
    db.session.add_all([
        test_ctx.tag_factory(names=['source'], category_name='meta'),
        test_ctx.tag_factory(names=['target'], category_name='meta'),
    ])
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={
                    'remove': 'source',
                    'mergeTo': 'target',
                },
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)))
