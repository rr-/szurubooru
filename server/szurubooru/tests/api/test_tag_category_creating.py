import os
import pytest
from szurubooru import api, config, db, errors
from szurubooru.func import util, tag_categories

@pytest.fixture
def test_ctx(tmpdir, config_injector, context_factory, user_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'tag_category_name_regex': '^[^!]+$',
        'privileges': {'tag_categories:create': db.User.RANK_REGULAR},
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.api = api.TagCategoryListApi()
    return ret

def test_creating_category(test_ctx):
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={'name': 'meta', 'color': 'black'},
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    assert result['tagCategory'] == {'name': 'meta', 'color': 'black'}
    assert len(result['snapshots']) == 1
    category = db.session.query(db.TagCategory).one()
    assert category.name == 'meta'
    assert category.color == 'black'
    assert category.tag_count == 0
    assert os.path.exists(os.path.join(config.config['data_dir'], 'tags.json'))

@pytest.mark.parametrize('input', [
    {'name': None},
    {'name': ''},
    {'name': '!bad'},
    {'color': None},
    {'color': ''},
    {'color': 'a' * 100},
])
def test_trying_to_pass_invalid_input(test_ctx, input):
    real_input = {
        'name': 'okay',
        'color': 'okay',
    }
    for key, value in input.items():
        real_input[key] = value
    with pytest.raises(errors.ValidationError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=real_input,
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

@pytest.mark.parametrize('field', ['name', 'color'])
def test_trying_to_omit_mandatory_field(test_ctx, field):
    input = {
        'name': 'meta',
        'color': 'black',
    }
    del input[field]
    with pytest.raises(errors.ValidationError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input=input,
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_use_existing_name(test_ctx):
    result = test_ctx.api.post(
        test_ctx.context_factory(
            input={'name': 'meta', 'color': 'black'},
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    with pytest.raises(tag_categories.TagCategoryAlreadyExistsError):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={'name': 'meta', 'color': 'black'},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))
    with pytest.raises(tag_categories.TagCategoryAlreadyExistsError):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={'name': 'META', 'color': 'black'},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_create_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={'name': 'meta', 'color': 'black'},
                user=test_ctx.user_factory(rank='anonymous')))
