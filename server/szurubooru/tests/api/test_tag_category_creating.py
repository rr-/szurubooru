import pytest
import unittest.mock
from szurubooru import api, db, errors
from szurubooru.func import tag_categories, tags

def _update_category_name(category, name):
    category.name = name

@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({
        'privileges': {'tag_categories:create': db.User.RANK_REGULAR},
    })

def test_creating_category(user_factory, context_factory):
    with unittest.mock.patch('szurubooru.func.tag_categories.serialize_category'), \
            unittest.mock.patch('szurubooru.func.tag_categories.update_category_name'), \
            unittest.mock.patch('szurubooru.func.tags.export_to_json'):
        tag_categories.update_category_name.side_effect = _update_category_name
        tag_categories.serialize_category.return_value = 'serialized category'
        result = api.tag_category_api.create_tag_category(
            context_factory(
                params={'name': 'meta', 'color': 'black'},
                user=user_factory(rank=db.User.RANK_REGULAR)))
        assert result == 'serialized category'
        category = db.session.query(db.TagCategory).one()
        assert category.name == 'meta'
        assert category.color == 'black'
        assert category.tag_count == 0
        tags.export_to_json.assert_called_once_with()

@pytest.mark.parametrize('field', ['name', 'color'])
def test_trying_to_omit_mandatory_field(user_factory, context_factory, field):
    params = {
        'name': 'meta',
        'color': 'black',
    }
    del params[field]
    with pytest.raises(errors.ValidationError):
        api.tag_category_api.create_tag_category(
            context_factory(
                params=params,
                user=user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_create_without_privileges(user_factory, context_factory):
    with pytest.raises(errors.AuthError):
        api.tag_category_api.create_tag_category(
            context_factory(
                params={'name': 'meta', 'color': 'black'},
                user=user_factory(rank=db.User.RANK_ANONYMOUS)))
