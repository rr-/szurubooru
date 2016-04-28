from szurubooru.api.base_api import BaseApi
from szurubooru.func import auth, tags, posts, snapshots, scores

class PostDetailApi(BaseApi):
    def get(self, ctx, post_id):
        auth.verify_privilege(ctx.user, 'posts:view')
        post = posts.get_post_by_id(post_id)
        return posts.serialize_post_with_details(post, ctx.user)

    def delete(self, ctx, post_id):
        auth.verify_privilege(ctx.user, 'posts:delete')
        post = posts.get_post_by_id(post_id)
        snapshots.delete(post, ctx.user)
        ctx.session.delete(post)
        ctx.session.commit()
        tags.export_to_json()
        return {}

class PostFeatureApi(BaseApi):
    def post(self, ctx):
        auth.verify_privilege(ctx.user, 'posts:feature')
        post_id = ctx.get_param_as_int('id', required=True)
        post = posts.get_post_by_id(post_id)
        featured_post = posts.try_get_featured_post()
        if featured_post and featured_post.post_id == post.post_id:
            raise posts.PostAlreadyFeaturedError(
                'Post %r is already featured.' % post_id)
        posts.feature_post(post, ctx.user)
        if featured_post:
            snapshots.modify(featured_post, ctx.user)
        snapshots.modify(post, ctx.user)
        ctx.session.commit()
        return posts.serialize_post_with_details(post, ctx.user)

    def get(self, ctx):
        post = posts.try_get_featured_post()
        return posts.serialize_post_with_details(post, ctx.user)

class PostScoreApi(BaseApi):
    def put(self, ctx, post_id):
        auth.verify_privilege(ctx.user, 'posts:score')
        post = posts.get_post_by_id(post_id)
        score = ctx.get_param_as_int('score', required=True)
        scores.set_score(post, ctx.user, score)
        ctx.session.commit()
        return posts.serialize_post_with_details(post, ctx.user)

    def delete(self, ctx, post_id):
        auth.verify_privilege(ctx.user, 'posts:score')
        post = posts.get_post_by_id(post_id)
        scores.delete_score(post, ctx.user)
        ctx.session.commit()
        return posts.serialize_post_with_details(post, ctx.user)
