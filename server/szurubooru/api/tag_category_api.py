from szurubooru.api.base_api import BaseApi
from szurubooru.func import auth, tags, tag_categories, snapshots

class TagCategoryListApi(BaseApi):
    def get(self, ctx):
        auth.verify_privilege(ctx.user, 'tag_categories:list')
        categories = tag_categories.get_all_categories()
        return {
            'results': [
                tag_categories.serialize_category(category) \
                    for category in categories],
        }

    def post(self, ctx):
        auth.verify_privilege(ctx.user, 'tag_categories:create')
        name = ctx.get_param_as_string('name', required=True)
        color = ctx.get_param_as_string('color', required=True)
        category = tag_categories.create_category(name, color)
        ctx.session.add(category)
        ctx.session.flush()
        snapshots.create(category, ctx.user)
        ctx.session.commit()
        tags.export_to_json()
        return tag_categories.serialize_category_with_details(category)

class TagCategoryDetailApi(BaseApi):
    def get(self, ctx, category_name):
        auth.verify_privilege(ctx.user, 'tag_categories:view')
        category = tag_categories.get_category_by_name(category_name)
        return tag_categories.serialize_category_with_details(category)

    def put(self, ctx, category_name):
        category = tag_categories.get_category_by_name(category_name)
        if ctx.has_param('name'):
            auth.verify_privilege(ctx.user, 'tag_categories:edit:name')
            tag_categories.update_name(
                category, ctx.get_param_as_string('name'))
        if ctx.has_param('color'):
            auth.verify_privilege(ctx.user, 'tag_categories:edit:color')
            tag_categories.update_color(
                category, ctx.get_param_as_string('color'))
        ctx.session.flush()
        snapshots.modify(category, ctx.user)
        ctx.session.commit()
        tags.export_to_json()
        return tag_categories.serialize_category_with_details(category)

    def delete(self, ctx, category_name):
        category = tag_categories.get_category_by_name(category_name)
        auth.verify_privilege(ctx.user, 'tag_categories:delete')
        if len(tag_categories.get_all_category_names()) == 1:
            raise tag_categories.TagCategoryIsInUseError(
                'Cannot delete the default category.')
        if category.tag_count > 0:
            raise tag_categories.TagCategoryIsInUseError(
                'Tag category has some usages and cannot be deleted. ' +
                'Please remove this category from relevant tags first..')
        snapshots.delete(category, ctx.user)
        ctx.session.delete(category)
        ctx.session.commit()
        tags.export_to_json()
        return {}
