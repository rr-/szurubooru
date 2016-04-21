import sqlalchemy
from szurubooru import db

def get_post_count():
    return db.session.query(sqlalchemy.func.count(db.Post.post_id)).one()[0]
