from datetime import datetime
from typing import Dict, List, Optional

from szurubooru import db, model, rest, search
from szurubooru.func import auth, serialization, snapshots, tags, versions, users

_search_executor = search.Executor(search.configs.TagSearchConfig())


def _serialize(ctx: rest.Context, tag: model.Tag) -> rest.Response:
    return tags.serialize_tag(
        tag, options=serialization.get_serialization_options(ctx)
    )


def _get_tag(params: Dict[str, str]) -> model.Tag:
    return tags.get_tag_by_name(params["tag_name"])


def _create_if_needed(tag_names: List[str], user: model.User) -> None:
    if not tag_names:
        return
    _existing_tags, new_tags = tags.get_or_create_tags_by_names(tag_names)
    if len(new_tags):
        auth.verify_privilege(user, "tags:create")
    db.session.flush()
    for tag in new_tags:
        snapshots.create(tag, user)


@rest.routes.get("/tags/?")
def get_tags(ctx: rest.Context, _params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, "tags:list")
    return _search_executor.execute_and_serialize(
        ctx, lambda tag: _serialize(ctx, tag)
    )


@rest.routes.post("/tags/?")
def create_tag(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    auth.verify_privilege(ctx.user, "tags:create")

    names = ctx.get_param_as_string_list("names")
    category = ctx.get_param_as_string("category")
    description = ctx.get_param_as_string("description", default="")
    suggestions = ctx.get_param_as_string_list("suggestions", default=[])
    implications = ctx.get_param_as_string_list("implications", default=[])

    _create_if_needed(suggestions, ctx.user)
    _create_if_needed(implications, ctx.user)

    tag = tags.create_tag(names, category, suggestions, implications)
    tags.update_tag_description(tag, description)
    ctx.session.add(tag)
    ctx.session.flush()
    snapshots.create(tag, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, tag)


@rest.routes.get("/tag/(?P<tag_name>.+)")
def get_tag(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    auth.verify_privilege(ctx.user, "tags:view")
    tag = _get_tag(params)
    return _serialize(ctx, tag)


@rest.routes.put("/tag/(?P<tag_name>.+)")
def update_tag(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    tag = _get_tag(params)
    versions.verify_version(tag, ctx)
    versions.bump_version(tag)
    if ctx.has_param("names"):
        auth.verify_privilege(ctx.user, "tags:edit:names")
        tags.update_tag_names(tag, ctx.get_param_as_string_list("names"))
    if ctx.has_param("category"):
        auth.verify_privilege(ctx.user, "tags:edit:category")
        tags.update_tag_category_name(tag, ctx.get_param_as_string("category"))
    if ctx.has_param("description"):
        auth.verify_privilege(ctx.user, "tags:edit:description")
        tags.update_tag_description(
            tag, ctx.get_param_as_string("description")
        )
    if ctx.has_param("suggestions"):
        auth.verify_privilege(ctx.user, "tags:edit:suggestions")
        suggestions = ctx.get_param_as_string_list("suggestions")
        _create_if_needed(suggestions, ctx.user)
        tags.update_tag_suggestions(tag, suggestions)
    if ctx.has_param("implications"):
        auth.verify_privilege(ctx.user, "tags:edit:implications")
        implications = ctx.get_param_as_string_list("implications")
        _create_if_needed(implications, ctx.user)
        tags.update_tag_implications(tag, implications)
    tag.last_edit_time = datetime.utcnow()
    ctx.session.flush()
    snapshots.modify(tag, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, tag)


@rest.routes.delete("/tag/(?P<tag_name>.+)")
def delete_tag(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    tag = _get_tag(params)
    versions.verify_version(tag, ctx)
    auth.verify_privilege(ctx.user, "tags:delete")
    snapshots.delete(tag, ctx.user)
    tags.delete(tag)
    ctx.session.commit()
    return {}


@rest.routes.post("/tag-merge/?")
def merge_tags(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    source_tag_name = ctx.get_param_as_string("remove")
    target_tag_name = ctx.get_param_as_string("mergeTo")
    source_tag = tags.get_tag_by_name(source_tag_name)
    target_tag = tags.get_tag_by_name(target_tag_name)
    versions.verify_version(source_tag, ctx, "removeVersion")
    versions.verify_version(target_tag, ctx, "mergeToVersion")
    versions.bump_version(target_tag)
    auth.verify_privilege(ctx.user, "tags:merge")
    tags.merge_tags(source_tag, target_tag)
    snapshots.merge(source_tag, target_tag, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, target_tag)


@rest.routes.get("/tag-siblings/(?P<tag_name>.+)")
def get_tag_siblings(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    auth.verify_privilege(ctx.user, "tags:view")
    tag = _get_tag(params)
    result = tags.get_tag_siblings(tag)
    serialized_siblings = []
    for sibling, occurrences in result:
        serialized_siblings.append(
            {"tag": _serialize(ctx, sibling), "occurrences": occurrences}
        )
    return {"results": serialized_siblings}
