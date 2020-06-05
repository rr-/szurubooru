from typing import Dict

from szurubooru import model, rest
from szurubooru.func import (
    auth,
    pool_categories,
    pools,
    serialization,
    snapshots,
    versions,
)


def _serialize(
    ctx: rest.Context, category: model.PoolCategory
) -> rest.Response:
    return pool_categories.serialize_category(
        category, options=serialization.get_serialization_options(ctx)
    )


@rest.routes.get("/pool-categories/?")
def get_pool_categories(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    auth.verify_privilege(ctx.user, "pool_categories:list")
    categories = pool_categories.get_all_categories()
    return {
        "results": [_serialize(ctx, category) for category in categories],
    }


@rest.routes.post("/pool-categories/?")
def create_pool_category(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    auth.verify_privilege(ctx.user, "pool_categories:create")
    name = ctx.get_param_as_string("name")
    color = ctx.get_param_as_string("color")
    category = pool_categories.create_category(name, color)
    ctx.session.add(category)
    ctx.session.flush()
    snapshots.create(category, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, category)


@rest.routes.get("/pool-category/(?P<category_name>[^/]+)/?")
def get_pool_category(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    auth.verify_privilege(ctx.user, "pool_categories:view")
    category = pool_categories.get_category_by_name(params["category_name"])
    return _serialize(ctx, category)


@rest.routes.put("/pool-category/(?P<category_name>[^/]+)/?")
def update_pool_category(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    category = pool_categories.get_category_by_name(
        params["category_name"], lock=True
    )
    versions.verify_version(category, ctx)
    versions.bump_version(category)
    if ctx.has_param("name"):
        auth.verify_privilege(ctx.user, "pool_categories:edit:name")
        pool_categories.update_category_name(
            category, ctx.get_param_as_string("name")
        )
    if ctx.has_param("color"):
        auth.verify_privilege(ctx.user, "pool_categories:edit:color")
        pool_categories.update_category_color(
            category, ctx.get_param_as_string("color")
        )
    ctx.session.flush()
    snapshots.modify(category, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, category)


@rest.routes.delete("/pool-category/(?P<category_name>[^/]+)/?")
def delete_pool_category(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    category = pool_categories.get_category_by_name(
        params["category_name"], lock=True
    )
    versions.verify_version(category, ctx)
    auth.verify_privilege(ctx.user, "pool_categories:delete")
    pool_categories.delete_category(category)
    snapshots.delete(category, ctx.user)
    ctx.session.commit()
    return {}


@rest.routes.put("/pool-category/(?P<category_name>[^/]+)/default/?")
def set_pool_category_as_default(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    auth.verify_privilege(ctx.user, "pool_categories:set_default")
    category = pool_categories.get_category_by_name(
        params["category_name"], lock=True
    )
    pool_categories.set_default_category(category)
    ctx.session.flush()
    snapshots.modify(category, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, category)
