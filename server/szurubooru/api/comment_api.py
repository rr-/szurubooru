from szurubooru.api.base_api import BaseApi
from szurubooru.func import auth, comments, posts

class CommentListApi(BaseApi):
    def get(self, ctx):
        raise NotImplementedError()

    def post(self, ctx):
        auth.verify_privilege(ctx.user, 'comments:create')

        text = ctx.get_param_as_string('text', required=True)
        post_id = ctx.get_param_as_int('postId', required=True)
        post = posts.get_post_by_id(post_id)
        if not post:
            raise posts.PostNotFoundError('Post %r not found.' % post_id)
        comment = comments.create_comment(ctx.user, post, text)
        ctx.session.add(comment)
        ctx.session.commit()
        return {'comment': comments.serialize_comment(comment, ctx.user)}

class CommentDetailApi(BaseApi):
    def get(self, ctx, comment_id):
        raise NotImplementedError()

    def put(self, ctx, comment_id):
        raise NotImplementedError()

    def delete(self, ctx, comment_id):
        raise NotImplementedError()
