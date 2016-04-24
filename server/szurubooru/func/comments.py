import datetime
from szurubooru import db, errors
from szurubooru.func import users, posts, scores

class CommentNotFoundError(errors.NotFoundError): pass
class EmptyCommentTextError(errors.ValidationError): pass

def serialize_comment(comment, authenticated_user):
    ret = {
        'id': comment.comment_id,
        'user': users.serialize_user(comment.user, authenticated_user),
        'post': posts.serialize_post(comment.post, authenticated_user),
        'text': comment.text,
        'creationTime': comment.creation_time,
        'lastEditTime': comment.last_edit_time,
    }
    if authenticated_user:
        ret['ownScore'] = scores.get_score(comment, authenticated_user)
    return ret

def try_get_comment_by_id(comment_id):
    return db.session \
        .query(db.Comment) \
        .filter(db.Comment.comment_id == comment_id) \
        .one_or_none()

def get_comment_by_id(comment_id):
    comment = try_get_comment_by_id(comment_id)
    if comment:
        return comment
    raise CommentNotFoundError('Comment %r not found.' % comment_id)

def create_comment(user, post, text):
    comment = db.Comment()
    comment.user = user
    comment.post = post
    update_comment_text(comment, text)
    comment.creation_time = datetime.datetime.now()
    return comment

def update_comment_text(comment, text):
    if not text:
        raise EmptyCommentTextError('Comment text cannot be empty.')
    comment.text = text
