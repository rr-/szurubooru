import pytest
import unittest.mock
from szurubooru import api, db, errors
from szurubooru.func import tags

@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({
        'privileges': {
            'tags:list': db.User.RANK_REGULAR,
            'tags:view': db.User.RANK_REGULAR,
        },
    })

def test_retrieving_multiple(user_factory, tag_factory, context_factory):
    tag1 = tag_factory(names=['t1'])
    tag2 = tag_factory(names=['t2'])
    db.session.add_all([tag1, tag2])
    with unittest.mock.patch('szurubooru.func.tags.serialize_tag'):
        tags.serialize_tag.return_value = 'serialized tag'
        result = api.tag_api.get_tags(
            context_factory(
                params={'query': '', 'page': 1},
                user=user_factory(rank=db.User.RANK_REGULAR)))
        assert result == {
            'query': '',
            'page': 1,
            'pageSize': 100,
            'total': 2,
            'results': ['serialized tag', 'serialized tag'],
        }

def test_trying_to_retrieve_multiple_without_privileges(
        user_factory, context_factory):
    with pytest.raises(errors.AuthError):
        api.tag_api.get_tags(
            context_factory(
                params={'query': '', 'page': 1},
                user=user_factory(rank=db.User.RANK_ANONYMOUS)))

def test_retrieving_single(user_factory, tag_factory, context_factory):
    db.session.add(tag_factory(names=['tag']))
    with unittest.mock.patch('szurubooru.func.tags.serialize_tag'):
        tags.serialize_tag.return_value = 'serialized tag'
        result = api.tag_api.get_tag(
            context_factory(
                user=user_factory(rank=db.User.RANK_REGULAR)),
            {'tag_name': 'tag'})
        assert result == 'serialized tag'

def test_trying_to_retrieve_single_non_existing(user_factory, context_factory):
    with pytest.raises(tags.TagNotFoundError):
        api.tag_api.get_tag(
            context_factory(
                user=user_factory(rank=db.User.RANK_REGULAR)),
            {'tag_name': '-'})

def test_trying_to_retrieve_single_without_privileges(
        user_factory, context_factory):
    with pytest.raises(errors.AuthError):
        api.tag_api.get_tag(
            context_factory(
                user=user_factory(rank=db.User.RANK_ANONYMOUS)),
            {'tag_name': '-'})
