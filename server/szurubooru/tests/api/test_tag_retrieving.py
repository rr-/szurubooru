import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.util import misc, tags

@pytest.fixture
def test_ctx(
        session, context_factory, config_injector, user_factory, tag_factory):
    config_injector({
        'privileges': {
            'tags:list': 'regular_user',
            'tags:view': 'regular_user',
        },
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {'regular_user': 'Peasant'},
    })
    ret = misc.dotdict()
    ret.session = session
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.detail_api = api.TagDetailApi()
    return ret

def test_retrieving_single(test_ctx):
    test_ctx.session.add(test_ctx.tag_factory(names=['tag']))
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
        }
    }

def test_retrieving_non_existing(test_ctx):
    with pytest.raises(tags.TagNotFoundError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank='regular_user')),
            '-')

def test_retrieving_single_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank='anonymous')),
            '-')
