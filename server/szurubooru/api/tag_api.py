from szurubooru import errors
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
        ctx.session.flush()
        ctx.session.commit()
        return {'tag': _serialize_tag(tag)}

class TagDetailApi(BaseApi):
    def get(self, ctx):
        raise NotImplementedError()

    def put(self, ctx):
        raise NotImplementedError()

    def delete(self, ctx):
        raise NotImplementedError()
