import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.func import util, posts

@pytest.fixture
def test_ctx(
        tmpdir, config_injector, context_factory, post_factory, user_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'data_url': 'http://example.com',
        'privileges': {'comments:create': db.User.RANK_REGULAR},
        'thumbnails': {'avatar_width': 200},
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.post_factory = post_factory
    ret.user_factory = user_factory
    ret.api = api.CommentListApi()
    return ret

def test_creating_comment(test_ctx, fake_datetime):
    post = test_ctx.post_factory()
    user = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    db.session.add_all([post, user])
    db.session.flush()
    with fake_datetime('1997-01-01'):
        result = test_ctx.api.post(
            test_ctx.context_factory(
                input={'text': 'input', 'postId': post.post_id},
                user=user))
    assert result['comment']['text'] == 'input'
    assert 'id' in result['comment']
    assert 'user' in result['comment']
    assert 'post' in result['comment']
    assert 'name' in result['comment']['user']
    assert 'id' in result['comment']['post']
    comment = db.session.query(db.Comment).one()
    assert comment.text == 'input'
    assert comment.creation_time == datetime.datetime(1997, 1, 1)
    assert comment.last_edit_time is None
    assert comment.user and comment.user.user_id == user.user_id
    assert comment.post and comment.post.post_id == post.post_id

@pytest.mark.parametrize('input', [
    {'text': None},
    {'text': ''},
    {'text': [None]},
    {'text': ['']},
])
def test_trying_to_pass_invalid_input(test_ctx, input):
    post = test_ctx.post_factory()
    user = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    db.session.add_all([post, user])
    db.session.flush()
    real_input = {'text': 'input', 'postId': post.post_id}
    for key, value in input.items():
        real_input[key] = value
    with pytest.raises(errors.ValidationError):
        test_ctx.api.post(
            test_ctx.context_factory(input=real_input, user=user))

@pytest.mark.parametrize('field', ['text', 'postId'])
def test_trying_to_omit_mandatory_field(test_ctx, field):
    input = {
        'text': 'input',
        'postId': 1,
    }
    del input[field]
    with pytest.raises(errors.ValidationError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_comment_non_existing(test_ctx):
    user = test_ctx.user_factory(rank=db.User.RANK_REGULAR)
    db.session.add_all([user])
    db.session.flush()
    with pytest.raises(posts.PostNotFoundError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={'text': 'bad', 'postId': 5}, user=user))

def test_trying_to_create_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.api.post(
            test_ctx.context_factory(
                input={},
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)))
