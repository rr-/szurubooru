from datetime import datetime
from typing import Dict, List, Optional
from szurubooru.api import post_api
from szurubooru.func import posts
from szurubooru.model.bans import PostBan

from szurubooru import db, errors, model, rest, search
from szurubooru.func import (
    auth,
    bans,
    serialization,
    snapshots,
    versions,
)

def _get_ban_by_hash(hash: str) -> Optional[PostBan]:
    try:
        return bans.get_bans_by_hash(hash)
    except:
        return None


_search_executor = search.Executor(search.configs.BanSearchConfig())


def _serialize(ctx: rest.Context, ban: model.PostBan) -> rest.Response:
    return bans.serialize_ban(
        ban, options=serialization.get_serialization_options(ctx)
    )


@rest.routes.delete("/post-ban/(?P<image_hash>[^/]+)/?")
def unban_post(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    auth.verify_privilege(ctx.user, "posts:ban:delete")
    ban = _get_ban_by_hash(params["image_hash"])
    bans.delete(ban)
    ctx.session.commit()
    return {}


@rest.routes.post("/post-ban/?")
def ban_post(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    auth.verify_privilege(ctx.user, "posts:ban:create")
    # post = post_api._get_post(params)
    post =  posts.get_post_by_id(ctx.get_param_as_int("post_id"))
    versions.verify_version(post, ctx)
    posts.ban(bans.create_ban(post))
    snapshots.delete(post, ctx.user)
    posts.delete(post)
    ctx.session.commit()
    return {}

@rest.routes.get("/post-ban/?")
def get_bans(ctx: rest.Context, _params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, "posts:ban:list")
    return _search_executor.execute_and_serialize(
        ctx, lambda ban: _serialize(ctx, ban)
    )
