import datetime
from szurubooru import db, search
from szurubooru.api.base_api import BaseApi
from szurubooru.func import auth, tags, util, snapshots

def _serialize(ctx, tag):
    return tags.serialize_tag(
        tag, options=util.get_serialization_options(ctx))

def _create_if_needed(tag_names, user):
    if not tag_names:
        return
    auth.verify_privilege(user, 'tags:create')
    _existing_tags, new_tags = tags.get_or_create_tags_by_names(tag_names)
    db.session.flush()
    for tag in new_tags:
        snapshots.save_entity_creation(tag, user)

class TagListApi(BaseApi):
    def __init__(self):
        super().__init__()
        self._search_executor = search.Executor(
            search.configs.TagSearchConfig())

    def get(self, ctx):
        auth.verify_privilege(ctx.user, 'tags:list')
        return self._search_executor.execute_and_serialize(
            ctx, lambda tag: _serialize(ctx, tag))

    def post(self, ctx):
        auth.verify_privilege(ctx.user, 'tags:create')

        names = ctx.get_param_as_list('names', required=True)
        category = ctx.get_param_as_string('category', required=True)
        description = ctx.get_param_as_string(
            'description', required=False, default=None)
        suggestions = ctx.get_param_as_list(
            'suggestions', required=False, default=[])
        implications = ctx.get_param_as_list(
            'implications', required=False, default=[])

        _create_if_needed(suggestions, ctx.user)
        _create_if_needed(implications, ctx.user)

        tag = tags.create_tag(names, category, suggestions, implications)
        tags.update_tag_description(tag, description)
        ctx.session.add(tag)
        ctx.session.flush()
        snapshots.save_entity_creation(tag, ctx.user)
        ctx.session.commit()
        tags.export_to_json()
        return _serialize(ctx, tag)

class TagDetailApi(BaseApi):
    def get(self, ctx, tag_name):
        auth.verify_privilege(ctx.user, 'tags:view')
        tag = tags.get_tag_by_name(tag_name)
        return _serialize(ctx, tag)

    def put(self, ctx, tag_name):
        tag = tags.get_tag_by_name(tag_name)
        if ctx.has_param('names'):
            auth.verify_privilege(ctx.user, 'tags:edit:names')
            tags.update_tag_names(tag, ctx.get_param_as_list('names'))
        if ctx.has_param('category'):
            auth.verify_privilege(ctx.user, 'tags:edit:category')
            tags.update_tag_category_name(
                tag, ctx.get_param_as_string('category'))
        if ctx.has_param('description'):
            auth.verify_privilege(ctx.user, 'tags:edit:description')
            tags.update_tag_description(
                tag, ctx.get_param_as_string('description', default=None))
        if ctx.has_param('suggestions'):
            auth.verify_privilege(ctx.user, 'tags:edit:suggestions')
            suggestions = ctx.get_param_as_list('suggestions')
            _create_if_needed(suggestions, ctx.user)
            tags.update_tag_suggestions(tag, suggestions)
        if ctx.has_param('implications'):
            auth.verify_privilege(ctx.user, 'tags:edit:implications')
            implications = ctx.get_param_as_list('implications')
            _create_if_needed(implications, ctx.user)
            tags.update_tag_implications(tag, implications)
        tag.last_edit_time = datetime.datetime.now()
        ctx.session.flush()
        snapshots.save_entity_modification(tag, ctx.user)
        ctx.session.commit()
        tags.export_to_json()
        return _serialize(ctx, tag)

    def delete(self, ctx, tag_name):
        tag = tags.get_tag_by_name(tag_name)
        if tag.post_count > 0:
            raise tags.TagIsInUseError(
                'Tag has some usages and cannot be deleted. ' +
                'Please untag relevant posts first.')
        auth.verify_privilege(ctx.user, 'tags:delete')
        snapshots.save_entity_deletion(tag, ctx.user)
        tags.delete(tag)
        ctx.session.commit()
        tags.export_to_json()
        return {}

class TagMergeApi(BaseApi):
    def post(self, ctx):
        source_tag_name = ctx.get_param_as_string('remove', required=True) or ''
        target_tag_name = ctx.get_param_as_string('mergeTo', required=True) or ''
        source_tag = tags.get_tag_by_name(source_tag_name)
        target_tag = tags.get_tag_by_name(target_tag_name)
        if source_tag.tag_id == target_tag.tag_id:
            raise tags.InvalidTagRelationError('Cannot merge tag with itself.')
        auth.verify_privilege(ctx.user, 'tags:merge')
        snapshots.save_entity_deletion(source_tag, ctx.user)
        tags.merge_tags(source_tag, target_tag)
        ctx.session.commit()
        tags.export_to_json()
        return _serialize(ctx, target_tag)

class TagSiblingsApi(BaseApi):
    def get(self, ctx, tag_name):
        auth.verify_privilege(ctx.user, 'tags:view')
        tag = tags.get_tag_by_name(tag_name)
        result = tags.get_tag_siblings(tag)
        serialized_siblings = []
        for sibling, occurrences in result:
            serialized_siblings.append({
                'tag': _serialize(ctx, sibling),
                'occurrences': occurrences
            })
        return {'results': serialized_siblings}
