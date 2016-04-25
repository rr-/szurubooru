import datetime
import sqlalchemy
from szurubooru import db, errors
from szurubooru.func import users, snapshots, scores, comments

class PostNotFoundError(errors.NotFoundError): pass
class PostAlreadyFeaturedError(errors.ValidationError): pass

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
        'user': users.serialize_user(post.user, authenticated_user),
        'score': post.score,
        'featureCount': post.feature_count,
        'lastFeatureTime': post.last_feature_time,
        'favoritedBy': [users.serialize_user(rel, authenticated_user) \
            for rel in post.favorited_by],
    }

    if authenticated_user:
        ret['ownScore'] = scores.get_score(post, authenticated_user)

    return ret

def serialize_post_with_details(post, authenticated_user):
    comment_list = []
    if post:
        for comment in post.comments:
            comment_list.append(
                comments.serialize_comment(comment, authenticated_user))
    return {
        'post': serialize_post(post, authenticated_user),
        'snapshots': snapshots.get_serialized_history(post),
        'comments': comment_list,
    }

def get_post_count():
    return db.session.query(sqlalchemy.func.count(db.Post.post_id)).one()[0]

def try_get_post_by_id(post_id):
    return db.session \
        .query(db.Post) \
        .filter(db.Post.post_id == post_id) \
        .one_or_none()

def get_post_by_id(post_id):
    post = try_get_post_by_id(post_id)
    if not post:
        raise PostNotFoundError('Post %r not found.' % post_id)
    return post

def try_get_featured_post():
    post_feature = db.session \
        .query(db.PostFeature) \
        .order_by(db.PostFeature.time.desc()) \
        .first()
    return post_feature.post if post_feature else None

def feature_post(post, user):
    post_feature = db.PostFeature()
    post_feature.time = datetime.datetime.now()
    post_feature.post = post
    post_feature.user = user
    db.session.add(post_feature)
