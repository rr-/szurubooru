import datetime
from szurubooru.util import auth, tags
from szurubooru.api.base_api import BaseApi

def _serialize_tag(tag):
    return {
        'names': [tag_name.name for tag_name in tag.names],
        'category': tag.category,
        'suggestions': [
            relation.child_tag.names[0].name for relation in tag.suggestions],
        'implications': [
            relation.child_tag.names[0].name for relation in tag.implications],
        'creationTime': tag.creation_time,
        'lastEditTime': tag.last_edit_time,
    }

class TagListApi(BaseApi):
    def get(self, ctx):
        raise NotImplementedError()

    def post(self, ctx):
        auth.verify_privilege(ctx.user, 'tags:create')

        names = ctx.get_param_as_list('names', required=True)
        category = ctx.get_param_as_string('category', required=True)
        suggestions = ctx.get_param_as_list('suggestions', required=True)
        implications = ctx.get_param_as_list('implications', required=True)

        tag = tags.create_tag(
            ctx.session, names, category, suggestions, implications)
        ctx.session.add(tag)
        ctx.session.commit()
        return {'tag': _serialize_tag(tag)}

class TagDetailApi(BaseApi):
    def get(self, ctx, tag_name):
        auth.verify_privilege(ctx.user, 'tags:view')
        tag = tags.get_by_name(ctx.session, tag_name)
        if not tag:
            raise tags.TagNotFoundError('Tag %r not found.' % tag_name)
        return {'tag': _serialize_tag(tag)}

    def put(self, ctx, tag_name):
        tag = tags.get_by_name(ctx.session, tag_name)
        if not tag:
            raise tags.TagNotFoundError('Tag %r not found.' % tag_name)

        if ctx.has_param('names'):
            auth.verify_privilege(ctx.user, 'tags:edit:names')
            tags.update_names(
                ctx.session, tag, ctx.get_param_as_list('names'))

        if ctx.has_param('category'):
            auth.verify_privilege(ctx.user, 'tags:edit:category')
            tags.update_category(tag, ctx.get_param_as_string('category'))

        if ctx.has_param('suggestions'):
            auth.verify_privilege(ctx.user, 'tags:edit:suggestions')
            tags.update_suggestions(
                ctx.session, tag, ctx.get_param_as_list('suggestions'))

        if ctx.has_param('implications'):
            auth.verify_privilege(ctx.user, 'tags:edit:implications')
            tags.update_implications(
                ctx.session, tag, ctx.get_param_as_list('implications'))

        tag.last_edit_time = datetime.datetime.now()
        ctx.session.commit()
        return {'tag': _serialize_tag(tag)}

    def delete(self, ctx, tag_name):
        tag = tags.get_by_name(ctx.session, tag_name)
        if not tag:
            raise tags.TagNotFoundError('Tag %r not found.' % tag_name)
        if tag.post_count > 0:
            raise tags.TagIsInUseError(
                'Tag has some usages and cannot be deleted. ' +
                'Please untag relevant posts first.')

        auth.verify_privilege(ctx.user, 'tags:delete')
        ctx.session.delete(tag)
        ctx.session.commit()
        return {}
