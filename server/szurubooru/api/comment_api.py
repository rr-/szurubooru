import datetime
from szurubooru import search
from szurubooru.api.base_api import BaseApi
from szurubooru.func import auth, comments, posts

class CommentListApi(BaseApi):
    def __init__(self):
        super().__init__()
        self._search_executor = search.SearchExecutor(
            search.CommentSearchConfig())

    def get(self, ctx):
        auth.verify_privilege(ctx.user, 'comments:list')
        return self._search_executor.execute_and_serialize(
            ctx,
            lambda comment: comments.serialize_comment(comment, ctx.user),
            'comments')

    def post(self, ctx):
        auth.verify_privilege(ctx.user, 'comments:create')
        text = ctx.get_param_as_string('text', required=True)
        post_id = ctx.get_param_as_int('postId', required=True)
        post = posts.get_post_by_id(post_id)
        comment = comments.create_comment(ctx.user, post, text)
        ctx.session.add(comment)
        ctx.session.commit()
        return {'comment': comments.serialize_comment(comment, ctx.user)}

class CommentDetailApi(BaseApi):
    def get(self, ctx, comment_id):
        auth.verify_privilege(ctx.user, 'comments:view')
        comment = comments.get_comment_by_id(comment_id)
        return {'comment': comments.serialize_comment(comment, ctx.user)}

    def put(self, ctx, comment_id):
        comment = comments.get_comment_by_id(comment_id)
        infix = 'self' if ctx.user.user_id == comment.user_id else 'any'
        text = ctx.get_param_as_string('text', required=True)
        auth.verify_privilege(ctx.user, 'comments:edit:%s' % infix)
        comment.last_edit_time = datetime.datetime.now()
        comments.update_comment_text(comment, text)
        ctx.session.commit()
        return {'comment': comments.serialize_comment(comment, ctx.user)}

    def delete(self, ctx, comment_id):
        comment = comments.get_comment_by_id(comment_id)
        infix = 'self' if ctx.user.user_id == comment.user_id else 'any'
        auth.verify_privilege(ctx.user, 'comments:delete:%s' % infix)
        ctx.session.delete(comment)
        ctx.session.commit()
        return {}
