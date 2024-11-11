import sqlalchemy as sa

from szurubooru.model.base import Base
from szurubooru.model.comment import Comment
from szurubooru.model.post import Post, PostFavorite, PostScore


class UserTagBlocklist(Base):
    __tablename__ = "user_tag_blocklist"

    user_id = sa.Column(
        "user_id",
        sa.Integer,
        sa.ForeignKey("user.id"),
        primary_key=True,
        nullable=False,
        index=True,
    )
    tag_id = sa.Column(
        "tag_id",
        sa.Integer,
        sa.ForeignKey("tag.id"),
        primary_key=True,
        nullable=False,
        index=True,
    )

    tag = sa.orm.relationship(
        "Tag",
        backref=sa.orm.backref("user_tag_blocklist", cascade="all, delete-orphan"),
    )
    user = sa.orm.relationship(
        "User",
        backref=sa.orm.backref("user_tag_blocklist", cascade="all, delete-orphan"),
    )

    def __init__(self, user_id: int=None, tag_id: int=None, user=None, tag=None) -> None:
        if user_id is not None:
            self.user_id = user_id
        if tag_id is not None:
            self.tag_id = tag_id
        if user is not None:
            self.user = user
        if tag is not None:
            self.tag = tag


class User(Base):
    __tablename__ = "user"

    AVATAR_GRAVATAR = "gravatar"
    AVATAR_MANUAL = "manual"

    RANK_ANONYMOUS = "anonymous"
    RANK_RESTRICTED = "restricted"
    RANK_REGULAR = "regular"
    RANK_POWER = "power"
    RANK_MODERATOR = "moderator"
    RANK_ADMINISTRATOR = "administrator"
    RANK_NOBODY = "nobody"  # unattainable, used for privileges

    user_id = sa.Column("id", sa.Integer, primary_key=True)
    creation_time = sa.Column("creation_time", sa.DateTime, nullable=False)
    last_login_time = sa.Column("last_login_time", sa.DateTime)
    version = sa.Column("version", sa.Integer, default=1, nullable=False)
    name = sa.Column("name", sa.Unicode(50), nullable=False, unique=True)
    password_hash = sa.Column("password_hash", sa.Unicode(128), nullable=False)
    password_salt = sa.Column("password_salt", sa.Unicode(32))
    password_revision = sa.Column(
        "password_revision", sa.SmallInteger, default=0, nullable=False
    )
    email = sa.Column("email", sa.Unicode(64), nullable=True)
    rank = sa.Column("rank", sa.Unicode(32), nullable=False)
    avatar_style = sa.Column(
        "avatar_style", sa.Unicode(32), nullable=False, default=AVATAR_GRAVATAR
    )

    blocklist = sa.orm.relationship("UserTagBlocklist")
    comments = sa.orm.relationship("Comment")

    @property
    def post_count(self) -> int:
        from szurubooru.db import session

        return (
            session.query(sa.sql.expression.func.sum(1))
            .filter(Post.user_id == self.user_id)
            .one()[0]
            or 0
        )

    @property
    def comment_count(self) -> int:
        from szurubooru.db import session

        return (
            session.query(sa.sql.expression.func.sum(1))
            .filter(Comment.user_id == self.user_id)
            .one()[0]
            or 0
        )

    @property
    def favorite_post_count(self) -> int:
        from szurubooru.db import session

        return (
            session.query(sa.sql.expression.func.sum(1))
            .filter(PostFavorite.user_id == self.user_id)
            .one()[0]
            or 0
        )

    @property
    def liked_post_count(self) -> int:
        from szurubooru.db import session

        return (
            session.query(sa.sql.expression.func.sum(1))
            .filter(PostScore.user_id == self.user_id)
            .filter(PostScore.score == 1)
            .one()[0]
            or 0
        )

    @property
    def disliked_post_count(self) -> int:
        from szurubooru.db import session

        return (
            session.query(sa.sql.expression.func.sum(1))
            .filter(PostScore.user_id == self.user_id)
            .filter(PostScore.score == -1)
            .one()[0]
            or 0
        )

    __mapper_args__ = {
        "version_id_col": version,
        "version_id_generator": False,
    }


class UserToken(Base):
    __tablename__ = "user_token"

    user_token_id = sa.Column("id", sa.Integer, primary_key=True)
    user_id = sa.Column(
        "user_id",
        sa.Integer,
        sa.ForeignKey("user.id", ondelete="CASCADE"),
        nullable=False,
        index=True,
    )
    token = sa.Column("token", sa.Unicode(36), nullable=False)
    note = sa.Column("note", sa.Unicode(128), nullable=True)
    enabled = sa.Column("enabled", sa.Boolean, nullable=False, default=True)
    expiration_time = sa.Column("expiration_time", sa.DateTime, nullable=True)
    creation_time = sa.Column("creation_time", sa.DateTime, nullable=False)
    last_edit_time = sa.Column("last_edit_time", sa.DateTime)
    last_usage_time = sa.Column("last_usage_time", sa.DateTime)
    version = sa.Column("version", sa.Integer, default=1, nullable=False)

    user = sa.orm.relationship("User")
