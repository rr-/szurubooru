import datetime
from szurubooru import search
from szurubooru.func import auth, comments, posts, scores, util
from szurubooru.rest import routes


_search_executor = search.Executor(search.configs.CommentSearchConfig())


def _serialize(ctx, comment, **kwargs):
    return comments.serialize_comment(
        comment,
        ctx.user,
        options=util.get_serialization_options(ctx), **kwargs)


@routes.get('/comments/?')
def get_comments(ctx, _params=None):
    auth.verify_privilege(ctx.user, 'comments:list')
    return _search_executor.execute_and_serialize(
        ctx, lambda comment: _serialize(ctx, comment))


@routes.post('/comments/?')
def create_comment(ctx, _params=None):
    auth.verify_privilege(ctx.user, 'comments:create')
    text = ctx.get_param_as_string('text', required=True)
    post_id = ctx.get_param_as_int('postId', required=True)
    post = posts.get_post_by_id(post_id)
    comment = comments.create_comment(ctx.user, post, text)
    ctx.session.add(comment)
    ctx.session.commit()
    return _serialize(ctx, comment)


@routes.get('/comment/(?P<comment_id>[^/]+)/?')
def get_comment(ctx, params):
    auth.verify_privilege(ctx.user, 'comments:view')
    comment = comments.get_comment_by_id(params['comment_id'])
    return _serialize(ctx, comment)


@routes.put('/comment/(?P<comment_id>[^/]+)/?')
def update_comment(ctx, params):
    comment = comments.get_comment_by_id(params['comment_id'])
    util.verify_version(comment, ctx)
    infix = 'own' if ctx.user.user_id == comment.user_id else 'any'
    text = ctx.get_param_as_string('text', required=True)
    auth.verify_privilege(ctx.user, 'comments:edit:%s' % infix)
    comments.update_comment_text(comment, text)
    util.bump_version(comment)
    comment.last_edit_time = datetime.datetime.utcnow()
    ctx.session.commit()
    return _serialize(ctx, comment)


@routes.delete('/comment/(?P<comment_id>[^/]+)/?')
def delete_comment(ctx, params):
    comment = comments.get_comment_by_id(params['comment_id'])
    util.verify_version(comment, ctx)
    infix = 'own' if ctx.user.user_id == comment.user_id else 'any'
    auth.verify_privilege(ctx.user, 'comments:delete:%s' % infix)
    ctx.session.delete(comment)
    ctx.session.commit()
    return {}


@routes.put('/comment/(?P<comment_id>[^/]+)/score/?')
def set_comment_score(ctx, params):
    auth.verify_privilege(ctx.user, 'comments:score')
    score = ctx.get_param_as_int('score', required=True)
    comment = comments.get_comment_by_id(params['comment_id'])
    scores.set_score(comment, ctx.user, score)
    ctx.session.commit()
    return _serialize(ctx, comment)


@routes.delete('/comment/(?P<comment_id>[^/]+)/score/?')
def delete_comment_score(ctx, params):
    auth.verify_privilege(ctx.user, 'comments:score')
    comment = comments.get_comment_by_id(params['comment_id'])
    scores.delete_score(comment, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, comment)
