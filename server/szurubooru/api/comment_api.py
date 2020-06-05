from datetime import datetime
from typing import Dict

from szurubooru import model, rest, search
from szurubooru.func import (
    auth,
    comments,
    posts,
    scores,
    serialization,
    versions,
)

_search_executor = search.Executor(search.configs.CommentSearchConfig())


def _get_comment(params: Dict[str, str]) -> model.Comment:
    try:
        comment_id = int(params["comment_id"])
    except TypeError:
        raise comments.InvalidCommentIdError(
            "Invalid comment ID: %r." % params["comment_id"]
        )
    return comments.get_comment_by_id(comment_id)


def _serialize(ctx: rest.Context, comment: model.Comment) -> rest.Response:
    return comments.serialize_comment(
        comment, ctx.user, options=serialization.get_serialization_options(ctx)
    )


@rest.routes.get("/comments/?")
def get_comments(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    auth.verify_privilege(ctx.user, "comments:list")
    return _search_executor.execute_and_serialize(
        ctx, lambda comment: _serialize(ctx, comment)
    )


@rest.routes.post("/comments/?")
def create_comment(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    auth.verify_privilege(ctx.user, "comments:create")
    text = ctx.get_param_as_string("text")
    post_id = ctx.get_param_as_int("postId")
    post = posts.get_post_by_id(post_id)
    comment = comments.create_comment(ctx.user, post, text)
    ctx.session.add(comment)
    ctx.session.commit()
    return _serialize(ctx, comment)


@rest.routes.get("/comment/(?P<comment_id>[^/]+)/?")
def get_comment(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    auth.verify_privilege(ctx.user, "comments:view")
    comment = _get_comment(params)
    return _serialize(ctx, comment)


@rest.routes.put("/comment/(?P<comment_id>[^/]+)/?")
def update_comment(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    comment = _get_comment(params)
    versions.verify_version(comment, ctx)
    versions.bump_version(comment)
    infix = "own" if ctx.user.user_id == comment.user_id else "any"
    text = ctx.get_param_as_string("text")
    auth.verify_privilege(ctx.user, "comments:edit:%s" % infix)
    comments.update_comment_text(comment, text)
    comment.last_edit_time = datetime.utcnow()
    ctx.session.commit()
    return _serialize(ctx, comment)


@rest.routes.delete("/comment/(?P<comment_id>[^/]+)/?")
def delete_comment(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    comment = _get_comment(params)
    versions.verify_version(comment, ctx)
    infix = "own" if ctx.user.user_id == comment.user_id else "any"
    auth.verify_privilege(ctx.user, "comments:delete:%s" % infix)
    ctx.session.delete(comment)
    ctx.session.commit()
    return {}


@rest.routes.put("/comment/(?P<comment_id>[^/]+)/score/?")
def set_comment_score(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    auth.verify_privilege(ctx.user, "comments:score")
    score = ctx.get_param_as_int("score")
    comment = _get_comment(params)
    scores.set_score(comment, ctx.user, score)
    ctx.session.commit()
    return _serialize(ctx, comment)


@rest.routes.delete("/comment/(?P<comment_id>[^/]+)/score/?")
def delete_comment_score(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    auth.verify_privilege(ctx.user, "comments:score")
    comment = _get_comment(params)
    scores.delete_score(comment, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, comment)
