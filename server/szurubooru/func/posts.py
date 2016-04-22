import datetime
import sqlalchemy
from szurubooru import db, errors

class PostNotFoundError(errors.NotFoundError): pass
class PostAlreadyFeaturedError(errors.ValidationError): pass

def get_post_count():
    return db.session.query(sqlalchemy.func.count(db.Post.post_id)).one()[0]

def get_post_by_id(post_id):
    return db.session.query(db.Post) \
        .filter(db.Post.post_id == post_id) \
        .one_or_none()

def get_featured_post():
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
