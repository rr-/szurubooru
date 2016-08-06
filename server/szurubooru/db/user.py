from sqlalchemy import Column, Integer, Unicode, DateTime
from sqlalchemy.orm import relationship
from sqlalchemy.sql.expression import func
from szurubooru.db.base import Base
from szurubooru.db.post import Post, PostScore, PostFavorite
from szurubooru.db.comment import Comment

class User(Base):
    __tablename__ = 'user'

    AVATAR_GRAVATAR = 'gravatar'
    AVATAR_MANUAL = 'manual'

    RANK_ANONYMOUS = 'anonymous'
    RANK_RESTRICTED = 'restricted'
    RANK_REGULAR = 'regular'
    RANK_POWER = 'power'
    RANK_MODERATOR = 'moderator'
    RANK_ADMINISTRATOR = 'administrator'
    RANK_NOBODY = 'nobody' # used for privileges: "nobody can be higher than admin"

    user_id = Column('id', Integer, primary_key=True)
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_login_time = Column('last_login_time', DateTime)
    version = Column('version', Integer, default=1, nullable=False)
    name = Column('name', Unicode(50), nullable=False, unique=True)
    password_hash = Column('password_hash', Unicode(64), nullable=False)
    password_salt = Column('password_salt', Unicode(32))
    email = Column('email', Unicode(64), nullable=True)
    rank = Column('rank', Unicode(32), nullable=False)
    avatar_style = Column(
        'avatar_style', Unicode(32), nullable=False, default=AVATAR_GRAVATAR)

    comments = relationship('Comment')

    @property
    def post_count(self):
        from szurubooru.db import session
        return session \
            .query(func.sum(1)) \
            .filter(Post.user_id == self.user_id) \
            .one()[0] or 0

    @property
    def comment_count(self):
        from szurubooru.db import session
        return session \
            .query(func.sum(1)) \
            .filter(Comment.user_id == self.user_id) \
            .one()[0] or 0

    @property
    def favorite_post_count(self):
        from szurubooru.db import session
        return session \
            .query(func.sum(1)) \
            .filter(PostFavorite.user_id == self.user_id) \
            .one()[0] or 0

    @property
    def liked_post_count(self):
        from szurubooru.db import session
        return session \
            .query(func.sum(1)) \
            .filter(PostScore.user_id == self.user_id) \
            .filter(PostScore.score == 1) \
            .one()[0] or 0

    @property
    def disliked_post_count(self):
        from szurubooru.db import session
        return session \
            .query(func.sum(1)) \
            .filter(PostScore.user_id == self.user_id) \
            .filter(PostScore.score == -1) \
            .one()[0] or 0
