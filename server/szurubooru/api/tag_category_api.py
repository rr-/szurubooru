from szurubooru.rest import routes
from szurubooru.func import (
    auth, tags, tag_categories, snapshots, util, versions)


def _serialize(ctx, category):
    return tag_categories.serialize_category(
        category, options=util.get_serialization_options(ctx))


@routes.get('/tag-categories/?')
def get_tag_categories(ctx, _params=None):
    auth.verify_privilege(ctx.user, 'tag_categories:list')
    categories = tag_categories.get_all_categories()
    return {
        'results': [_serialize(ctx, category) for category in categories],
    }


@routes.post('/tag-categories/?')
def create_tag_category(ctx, _params=None):
    auth.verify_privilege(ctx.user, 'tag_categories:create')
    name = ctx.get_param_as_string('name', required=True)
    color = ctx.get_param_as_string('color', required=True)
    category = tag_categories.create_category(name, color)
    ctx.session.add(category)
    ctx.session.flush()
    snapshots.create(category, ctx.user)
    ctx.session.commit()
    tags.export_to_json()
    return _serialize(ctx, category)


@routes.get('/tag-category/(?P<category_name>[^/]+)/?')
def get_tag_category(ctx, params):
    auth.verify_privilege(ctx.user, 'tag_categories:view')
    category = tag_categories.get_category_by_name(params['category_name'])
    return _serialize(ctx, category)


@routes.put('/tag-category/(?P<category_name>[^/]+)/?')
def update_tag_category(ctx, params):
    category = tag_categories.get_category_by_name(params['category_name'])
    versions.verify_version(category, ctx)
    versions.bump_version(category)
    if ctx.has_param('name'):
        auth.verify_privilege(ctx.user, 'tag_categories:edit:name')
        tag_categories.update_category_name(
            category, ctx.get_param_as_string('name'))
    if ctx.has_param('color'):
        auth.verify_privilege(ctx.user, 'tag_categories:edit:color')
        tag_categories.update_category_color(
            category, ctx.get_param_as_string('color'))
    ctx.session.flush()
    snapshots.modify(category, ctx.user)
    ctx.session.commit()
    tags.export_to_json()
    return _serialize(ctx, category)


@routes.delete('/tag-category/(?P<category_name>[^/]+)/?')
def delete_tag_category(ctx, params):
    category = tag_categories.get_category_by_name(params['category_name'])
    versions.verify_version(category, ctx)
    auth.verify_privilege(ctx.user, 'tag_categories:delete')
    tag_categories.delete_category(category)
    snapshots.delete(category, ctx.user)
    ctx.session.commit()
    tags.export_to_json()
    return {}


@routes.put('/tag-category/(?P<category_name>[^/]+)/default/?')
def set_tag_category_as_default(ctx, params):
    auth.verify_privilege(ctx.user, 'tag_categories:set_default')
    category = tag_categories.get_category_by_name(params['category_name'])
    tag_categories.set_default_category(category)
    snapshots.modify(category, ctx.user)
    ctx.session.commit()
    tags.export_to_json()
    return _serialize(ctx, category)
