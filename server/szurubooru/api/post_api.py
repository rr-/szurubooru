from szurubooru.api.base_api import BaseApi
from szurubooru.api.user_api import serialize_user
from szurubooru.func import auth, posts, snapshots

def serialize_post(post, authenticated_user):
    if not post:
        return None

    ret = {
        'id': post.post_id,
        'creationTime': post.creation_time,
        'lastEditTime': post.last_edit_time,
        'safety': post.safety,
        'type': post.type,
        'checksum': post.checksum,
        'source': post.source,
        'fileSize': post.file_size,
        'canvasWidth': post.canvas_width,
        'canvasHeight': post.canvas_height,
        'flags': post.flags,
        'tags': [tag.first_name for tag in post.tags],
        'relations': [rel.post_id for rel in post.relations],
        'notes': sorted([{
            'path': note.path,
            'text': note.text,
        } for note in post.notes]),
        'user': serialize_user(post.user, authenticated_user),
        'score': post.score,
        'featureCount': post.feature_count,
        'lastFeatureTime': post.last_feature_time,
        'favoritedBy': [serialize_user(rel, authenticated_user) \
            for rel in post.favorited_by],
    }

    # TODO: fetch own score if needed

    return ret

def serialize_post_with_details(post, authenticated_user):
    return {
        'post': serialize_post(post, authenticated_user),
        'snapshots': snapshots.get_serialized_history(post),
    }

class PostFeatureApi(BaseApi):
    def post(self, ctx):
        auth.verify_privilege(ctx.user, 'posts:feature')
        post_id = ctx.get_param_as_int('id', required=True)
        post = posts.get_post_by_id(post_id)
        if not post:
            raise posts.PostNotFoundError('Post %r not found.' % post_id)
        featured_post = posts.get_featured_post()
        if featured_post and featured_post.post_id == post.post_id:
            raise posts.PostAlreadyFeaturedError(
                'Post %r is already featured.' % post_id)
        posts.feature_post(post, ctx.user)
        if featured_post:
            snapshots.modify(featured_post, ctx.user)
        snapshots.modify(post, ctx.user)
        ctx.session.commit()
        return serialize_post_with_details(post, ctx.user)

    def get(self, ctx):
        post = posts.get_featured_post()
        return serialize_post_with_details(post, ctx.user)
