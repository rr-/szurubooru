import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.func import util, comments

@pytest.fixture
def test_ctx(context_factory, config_injector, user_factory, comment_factory):
    config_injector({
        'privileges': {
            'comments:list': 'regular_user',
            'comments:view': 'regular_user',
        },
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {'regular_user': 'Peasant'},
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.comment_factory = comment_factory
    ret.list_api = api.CommentListApi()
    ret.detail_api = api.CommentDetailApi()
    return ret

def test_retrieving_multiple(test_ctx):
    comment1 = test_ctx.comment_factory(text='text 1')
    comment2 = test_ctx.comment_factory(text='text 2')
    db.session.add_all([comment1, comment2])
    result = test_ctx.list_api.get(
        test_ctx.context_factory(
            input={'query': '', 'page': 1},
            user=test_ctx.user_factory(rank='regular_user')))
    assert result['query'] == ''
    assert result['page'] == 1
    assert result['pageSize'] == 100
    assert result['total'] == 2
    assert [c['text'] for c in result['results']] == ['text 1', 'text 2']

def test_trying_to_retrieve_multiple_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.list_api.get(
            test_ctx.context_factory(
                input={'query': '', 'page': 1},
                user=test_ctx.user_factory(rank='anonymous')))

def test_retrieving_single(test_ctx):
    comment = test_ctx.comment_factory(text='dummy text')
    db.session.add(comment)
    db.session.flush()
    result = test_ctx.detail_api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank='regular_user')),
        comment.comment_id)
    assert 'comment' in result
    assert 'id' in result['comment']
    assert 'lastEditTime' in result['comment']
    assert 'creationTime' in result['comment']
    assert 'text' in result['comment']
    assert 'user' in result['comment']
    assert 'name' in result['comment']['user']
    assert 'post' in result['comment']
    assert 'id' in result['comment']['post']

def test_trying_to_retrieve_single_non_existing(test_ctx):
    with pytest.raises(comments.CommentNotFoundError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank='regular_user')),
            5)

def test_trying_to_retrieve_single_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.detail_api.get(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank='anonymous')),
            5)
