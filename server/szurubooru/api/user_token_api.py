from typing import Dict

from szurubooru import model, rest
from szurubooru.func import auth, user_tokens, serialization, versions


def _serialize(
        ctx: rest.Context, user_token: model.UserToken) -> rest.Response:
    return user_tokens.serialize_user_token(
        user_token,
        ctx.user,
        options=serialization.get_serialization_options(ctx))


@rest.routes.get('/user-tokens/?')
def get_user_tokens(ctx: rest.Context, _params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, 'user_token:list')
    user_token_list = user_tokens.get_user_tokens(ctx.user)
    return {
        "results": [_serialize(ctx, token) for token in user_token_list]
    }


@rest.routes.post('/user-token/?')
def create_user_token(ctx: rest.Context, _params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, 'user_token:create')
    user_token = user_tokens.create_user_token(ctx.user)
    return _serialize(ctx, user_token)


@rest.routes.put('/user-token/(?P<user_token>[^/]+)/?')
def edit_user_token(ctx: rest.Context, _params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, 'user_token:edit')
    user_token = user_tokens.get_user_token_by_user_and_token(ctx.user, params['user_token'])
    versions.verify_version(user_token, ctx)
    versions.bump_version(user_token)
    return _serialize(ctx, user_token)


@rest.routes.delete('/user-token/(?P<user_token>[^/]+)/?')
def delete_user_token(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    auth.verify_privilege(ctx.user, 'user_token:delete')
    user_token = user_tokens.get_user_token_by_user_and_token(ctx.user, params['user_token'])
    if user_token is not None:
        ctx.session.delete(user_token)
        ctx.session.commit()
    return {}
