from typing import Dict

from szurubooru import model, rest
from szurubooru.func import auth, serialization, user_tokens, users, versions


def _serialize(
    ctx: rest.Context, user_token: model.UserToken
) -> rest.Response:
    return user_tokens.serialize_user_token(
        user_token,
        ctx.user,
        options=serialization.get_serialization_options(ctx),
    )


@rest.routes.get("/user-tokens/(?P<user_name>[^/]+)/?")
def get_user_tokens(
    ctx: rest.Context, params: Dict[str, str] = {}
) -> rest.Response:
    user = users.get_user_by_name(params["user_name"])
    infix = "self" if ctx.user.user_id == user.user_id else "any"
    auth.verify_privilege(ctx.user, "user_tokens:list:%s" % infix)
    user_token_list = user_tokens.get_user_tokens(user)
    return {"results": [_serialize(ctx, token) for token in user_token_list]}


@rest.routes.post("/user-token/(?P<user_name>[^/]+)/?")
def create_user_token(
    ctx: rest.Context, params: Dict[str, str] = {}
) -> rest.Response:
    user = users.get_user_by_name(params["user_name"])
    infix = "self" if ctx.user.user_id == user.user_id else "any"
    auth.verify_privilege(ctx.user, "user_tokens:create:%s" % infix)
    enabled = ctx.get_param_as_bool("enabled", True)
    user_token = user_tokens.create_user_token(user, enabled)
    if ctx.has_param("note"):
        note = ctx.get_param_as_string("note")
        user_tokens.update_user_token_note(user_token, note)
    if ctx.has_param("expirationTime"):
        expiration_time = ctx.get_param_as_string("expirationTime")
        user_tokens.update_user_token_expiration_time(
            user_token, expiration_time
        )
    ctx.session.add(user_token)
    ctx.session.commit()
    return _serialize(ctx, user_token)


@rest.routes.put("/user-token/(?P<user_name>[^/]+)/(?P<user_token>[^/]+)/?")
def update_user_token(
    ctx: rest.Context, params: Dict[str, str] = {}
) -> rest.Response:
    user = users.get_user_by_name(params["user_name"])
    infix = "self" if ctx.user.user_id == user.user_id else "any"
    auth.verify_privilege(ctx.user, "user_tokens:edit:%s" % infix)
    user_token = user_tokens.get_by_user_and_token(user, params["user_token"])
    versions.verify_version(user_token, ctx)
    versions.bump_version(user_token)
    if ctx.has_param("enabled"):
        auth.verify_privilege(ctx.user, "user_tokens:edit:%s" % infix)
        user_tokens.update_user_token_enabled(
            user_token, ctx.get_param_as_bool("enabled")
        )
    if ctx.has_param("note"):
        auth.verify_privilege(ctx.user, "user_tokens:edit:%s" % infix)
        note = ctx.get_param_as_string("note")
        user_tokens.update_user_token_note(user_token, note)
    if ctx.has_param("expirationTime"):
        auth.verify_privilege(ctx.user, "user_tokens:edit:%s" % infix)
        expiration_time = ctx.get_param_as_string("expirationTime")
        user_tokens.update_user_token_expiration_time(
            user_token, expiration_time
        )
    user_tokens.update_user_token_edit_time(user_token)
    ctx.session.commit()
    return _serialize(ctx, user_token)


@rest.routes.delete("/user-token/(?P<user_name>[^/]+)/(?P<user_token>[^/]+)/?")
def delete_user_token(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    user = users.get_user_by_name(params["user_name"])
    infix = "self" if ctx.user.user_id == user.user_id else "any"
    auth.verify_privilege(ctx.user, "user_tokens:delete:%s" % infix)
    user_token = user_tokens.get_by_user_and_token(user, params["user_token"])
    if user_token is not None:
        ctx.session.delete(user_token)
        ctx.session.commit()
    return {}
