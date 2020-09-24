from typing import Dict

from szurubooru import model, rest
from szurubooru.func import (
    auth,
    serialization,
    snapshots,
    tag_categories,
    tags,
    versions,
)


def _serialize(
    ctx: rest.Context, category: model.TagCategory
) -> rest.Response:
    return tag_categories.serialize_category(
        category, options=serialization.get_serialization_options(ctx)
    )


@rest.routes.get("/tag-categories/?")
def get_tag_categories(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    auth.verify_privilege(ctx.user, "tag_categories:list")
    categories = tag_categories.get_all_categories()
    return {
        "results": [_serialize(ctx, category) for category in categories],
    }


@rest.routes.post("/tag-categories/?")
def create_tag_category(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    auth.verify_privilege(ctx.user, "tag_categories:create")
    name = ctx.get_param_as_string("name")
    color = ctx.get_param_as_string("color")
    order = ctx.get_param_as_int("order")
    category = tag_categories.create_category(name, color, order)
    ctx.session.add(category)
    ctx.session.flush()
    snapshots.create(category, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, category)


@rest.routes.get("/tag-category/(?P<category_name>[^/]+)/?")
def get_tag_category(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    auth.verify_privilege(ctx.user, "tag_categories:view")
    category = tag_categories.get_category_by_name(params["category_name"])
    return _serialize(ctx, category)


@rest.routes.put("/tag-category/(?P<category_name>[^/]+)/?")
def update_tag_category(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    category = tag_categories.get_category_by_name(
        params["category_name"], lock=True
    )
    versions.verify_version(category, ctx)
    versions.bump_version(category)
    if ctx.has_param("name"):
        auth.verify_privilege(ctx.user, "tag_categories:edit:name")
        tag_categories.update_category_name(
            category, ctx.get_param_as_string("name")
        )
    if ctx.has_param("color"):
        auth.verify_privilege(ctx.user, "tag_categories:edit:color")
        tag_categories.update_category_color(
            category, ctx.get_param_as_string("color")
        )
    if ctx.has_param("order"):
        auth.verify_privilege(ctx.user, "tag_categories:edit:order")
        tag_categories.update_category_order(
            category, ctx.get_param_as_int("order")
        )
    ctx.session.flush()
    snapshots.modify(category, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, category)


@rest.routes.delete("/tag-category/(?P<category_name>[^/]+)/?")
def delete_tag_category(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    category = tag_categories.get_category_by_name(
        params["category_name"], lock=True
    )
    versions.verify_version(category, ctx)
    auth.verify_privilege(ctx.user, "tag_categories:delete")
    tag_categories.delete_category(category)
    snapshots.delete(category, ctx.user)
    ctx.session.commit()
    return {}


@rest.routes.put("/tag-category/(?P<category_name>[^/]+)/default/?")
def set_tag_category_as_default(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    auth.verify_privilege(ctx.user, "tag_categories:set_default")
    category = tag_categories.get_category_by_name(
        params["category_name"], lock=True
    )
    tag_categories.set_default_category(category)
    ctx.session.flush()
    snapshots.modify(category, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, category)
