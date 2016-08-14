import pytest
import unittest.mock
from szurubooru import api, db, errors
from szurubooru.func import tags, tag_categories

@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({'privileges': {'tags:create': db.User.RANK_REGULAR}})

def test_creating_simple_tags(tag_factory, user_factory, context_factory):
    with unittest.mock.patch('szurubooru.func.tags.create_tag'), \
            unittest.mock.patch('szurubooru.func.tags.get_or_create_tags_by_names'), \
            unittest.mock.patch('szurubooru.func.tags.serialize_tag'), \
            unittest.mock.patch('szurubooru.func.tags.export_to_json'):
        tags.get_or_create_tags_by_names.return_value = ([], [])
        tags.create_tag.return_value = tag_factory()
        tags.serialize_tag.return_value = 'serialized tag'
        result = api.tag_api.create_tag(
            context_factory(
                params={
                    'names': ['tag1', 'tag2'],
                    'category': 'meta',
                    'description': 'desc',
                    'suggestions': ['sug1', 'sug2'],
                    'implications': ['imp1', 'imp2'],
                },
                user=user_factory(rank=db.User.RANK_REGULAR)))
        assert result == 'serialized tag'
        tags.create_tag.assert_called_once_with(
            ['tag1', 'tag2'], 'meta', ['sug1', 'sug2'], ['imp1', 'imp2'])
        tags.export_to_json.assert_called_once_with()

@pytest.mark.parametrize('field', ['names', 'category'])
def test_trying_to_omit_mandatory_field(user_factory, context_factory, field):
    params = {
        'names': ['tag1', 'tag2'],
        'category': 'meta',
        'suggestions': [],
        'implications': [],
    }
    del params[field]
    with pytest.raises(errors.ValidationError):
        api.tag_api.create_tag(
            context_factory(
                params=params,
                user=user_factory(rank=db.User.RANK_REGULAR)))

@pytest.mark.parametrize('field', ['implications', 'suggestions'])
def test_omitting_optional_field(
        tag_factory, user_factory, context_factory, field):
    params = {
        'names': ['tag1', 'tag2'],
        'category': 'meta',
        'suggestions': [],
        'implications': [],
    }
    del params[field]
    with unittest.mock.patch('szurubooru.func.tags.create_tag'), \
            unittest.mock.patch('szurubooru.func.tags.serialize_tag'), \
            unittest.mock.patch('szurubooru.func.tags.export_to_json'):
        tags.create_tag.return_value = tag_factory()
        api.tag_api.create_tag(
            context_factory(
                params=params,
                user=user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_create_tag_without_privileges(user_factory, context_factory):
    with pytest.raises(errors.AuthError):
        api.tag_api.create_tag(
            context_factory(
                params={
                    'names': ['tag'],
                    'category': 'meta',
                    'suggestions': ['tag'],
                    'implications': [],
                },
                user=user_factory(rank=db.User.RANK_ANONYMOUS)))
