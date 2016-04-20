import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.func import util, tags

@pytest.fixture
def test_ctx(context_factory, config_injector, user_factory, tag_factory):
    config_injector({
        'privileges': {
            'tags:list': 'regular_user',
            'tags:view': 'regular_user',
        },
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {'regular_user': 'Peasant'},
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.list_api = api.TagListApi()
    ret.detail_api = api.TagDetailApi()
    return ret

def test_retrieving_multiple(test_ctx):
    tag1 = test_ctx.tag_factory(names=['t1'])
    tag2 = test_ctx.tag_factory(names=['t2'])
    db.session.add_all([tag1, tag2])
    result = test_ctx.list_api.get(
        test_ctx.context_factory(
            input={'query': '', 'page': 1},
            user=test_ctx.user_factory(rank='regular_user')))
    assert result['query'] == ''
    assert result['page'] == 1
    assert result['pageSize'] == 100
    assert result['total'] == 2
    assert [t['names'] for t in result['tags']] == [['t1'], ['t2']]

def test_trying_to_retrieve_multiple_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.list_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank='anonymous')))

def test_retrieving_single(test_ctx):
    db.session.add(test_ctx.tag_factory(names=['tag']))
    result = test_ctx.detail_api.get(
        test_ctx.context_factory(
            input={'query': '', 'page': 1},
            user=test_ctx.user_factory(rank='regular_user')),
        'tag')
    assert result == {
        'tag': {
            'names': ['tag'],
            'category': 'dummy',
            'creationTime': datetime.datetime(1996, 1, 1),
            'lastEditTime': None,
            'suggestions': [],
            'implications': [],
        },
        'snapshots': [],
    }

def test_trying_to_retrieve_single_non_existing(test_ctx):
    with pytest.raises(tags.TagNotFoundError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank='regular_user')),
            '-')

def test_trying_to_retrieve_single_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank='anonymous')),
            '-')
