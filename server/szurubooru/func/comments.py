import datetime
from szurubooru import db, errors
from szurubooru.func import users, scores, util

class CommentNotFoundError(errors.NotFoundError): pass
class EmptyCommentTextError(errors.ValidationError): pass

def serialize_comment(comment, authenticated_user, options=None):
    return util.serialize_entity(
        comment,
        {
            'id': lambda: comment.comment_id,
            'user': lambda: users.serialize_micro_user(comment.user),
            'postId': lambda: comment.post.post_id,
            'text': lambda: comment.text,
            'creationTime': lambda: comment.creation_time,
            'lastEditTime': lambda: comment.last_edit_time,
            'ownScore': lambda: scores.get_score(comment, authenticated_user),
        },
        options)

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
