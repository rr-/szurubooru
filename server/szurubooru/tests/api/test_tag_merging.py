import pytest
import unittest.mock
from szurubooru import api, db, errors
from szurubooru.func import tags

@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({'privileges': {'tags:merge': db.User.RANK_REGULAR}})

def test_merging(user_factory, tag_factory, context_factory, post_factory):
    source_tag = tag_factory(names=['source'])
    target_tag = tag_factory(names=['target'])
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
    with unittest.mock.patch('szurubooru.func.tags.serialize_tag'), \
            unittest.mock.patch('szurubooru.func.tags.merge_tags'), \
            unittest.mock.patch('szurubooru.func.tags.export_to_json'):
        result = api.tag_api.merge_tags(
            context_factory(
                params={
                    'removeVersion': 1,
                    'mergeToVersion': 1,
                    'remove': 'source',
                    'mergeTo': 'target',
                },
                user=user_factory(rank=db.User.RANK_REGULAR)))
        tags.merge_tags.called_once_with(source_tag, target_tag)
        tags.export_to_json.assert_called_once_with()

@pytest.mark.parametrize(
    'field', ['remove', 'mergeTo', 'removeVersion', 'mergeToVersion'])
def test_trying_to_omit_mandatory_field(
        user_factory, tag_factory, context_factory, field):
    db.session.add_all([
        tag_factory(names=['source']),
        tag_factory(names=['target']),
    ])
    db.session.commit()
    params = {
        'removeVersion': 1,
        'mergeToVersion': 1,
        'remove': 'source',
        'mergeTo': 'target',
    }
    del params[field]
    with pytest.raises(errors.ValidationError):
        api.tag_api.merge_tags(
            context_factory(
                params=params,
                user=user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_merge_non_existing(
        user_factory, tag_factory, context_factory):
    db.session.add(tag_factory(names=['good']))
    db.session.commit()
    with pytest.raises(tags.TagNotFoundError):
        api.tag_api.merge_tags(
            context_factory(
                params={'remove': 'good', 'mergeTo': 'bad'},
                user=user_factory(rank=db.User.RANK_REGULAR)))
    with pytest.raises(tags.TagNotFoundError):
        api.tag_api.merge_tags(
            context_factory(
                params={'remove': 'bad', 'mergeTo': 'good'},
                user=user_factory(rank=db.User.RANK_REGULAR)))

@pytest.mark.parametrize('params', [
    {'names': 'whatever'},
    {'category': 'whatever'},
    {'suggestions': ['whatever']},
    {'implications': ['whatever']},
])
def test_trying_to_merge_without_privileges(
        user_factory, tag_factory, context_factory, params):
    db.session.add_all([
        tag_factory(names=['source']),
        tag_factory(names=['target']),
    ])
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.tag_api.merge_tags(
            context_factory(
                params={
                    'removeVersion': 1,
                    'mergeToVersion': 1,
                    'remove': 'source',
                    'mergeTo': 'target',
                },
                user=user_factory(rank=db.User.RANK_ANONYMOUS)))
