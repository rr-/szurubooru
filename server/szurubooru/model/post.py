from sqlalchemy.sql.expression import func, select
from sqlalchemy import (
    Column, Integer, DateTime, Unicode, UnicodeText, PickleType, ForeignKey)
from sqlalchemy.orm import (
    relationship, column_property, object_session, backref)
from szurubooru.model.base import Base
from szurubooru.model.comment import Comment


class PostFeature(Base):
    __tablename__ = 'post_feature'

    post_feature_id = Column('id', Integer, primary_key=True)
    post_id = Column(
        'post_id', Integer, ForeignKey('post.id'), nullable=False, index=True)
    user_id = Column(
        'user_id', Integer, ForeignKey('user.id'), nullable=False, index=True)
    time = Column('time', DateTime, nullable=False)

    post = relationship('Post')  # type: Post
    user = relationship(
        'User', backref=backref('post_features', cascade='all, delete-orphan'))


class PostScore(Base):
    __tablename__ = 'post_score'

    post_id = Column(
        'post_id',
        Integer,
        ForeignKey('post.id'),
        primary_key=True,
        nullable=False,
        index=True)
    user_id = Column(
        'user_id',
        Integer,
        ForeignKey('user.id'),
        primary_key=True,
        nullable=False,
        index=True)
    time = Column('time', DateTime, nullable=False)
    score = Column('score', Integer, nullable=False)

    post = relationship('Post')
    user = relationship(
        'User',
        backref=backref('post_scores', cascade='all, delete-orphan'))


class PostFavorite(Base):
    __tablename__ = 'post_favorite'

    post_id = Column(
        'post_id',
        Integer,
        ForeignKey('post.id'),
        primary_key=True,
        nullable=False,
        index=True)
    user_id = Column(
        'user_id',
        Integer,
        ForeignKey('user.id'),
        primary_key=True,
        nullable=False,
        index=True)
    time = Column('time', DateTime, nullable=False)

    post = relationship('Post')
    user = relationship(
        'User',
        backref=backref('post_favorites', cascade='all, delete-orphan'))


class PostNote(Base):
    __tablename__ = 'post_note'

    post_note_id = Column('id', Integer, primary_key=True)
    post_id = Column(
        'post_id', Integer, ForeignKey('post.id'), nullable=False, index=True)
    polygon = Column('polygon', PickleType, nullable=False)
    text = Column('text', UnicodeText, nullable=False)

    post = relationship('Post')


class PostRelation(Base):
    __tablename__ = 'post_relation'

    parent_id = Column(
        'parent_id',
        Integer,
        ForeignKey('post.id'),
        primary_key=True,
        nullable=False,
        index=True)
    child_id = Column(
        'child_id',
        Integer,
        ForeignKey('post.id'),
        primary_key=True,
        nullable=False,
        index=True)

    def __init__(self, parent_id: int, child_id: int) -> None:
        self.parent_id = parent_id
        self.child_id = child_id


class PostTag(Base):
    __tablename__ = 'post_tag'

    post_id = Column(
        'post_id',
        Integer,
        ForeignKey('post.id'),
        primary_key=True,
        nullable=False,
        index=True)
    tag_id = Column(
        'tag_id',
        Integer,
        ForeignKey('tag.id'),
        primary_key=True,
        nullable=False,
        index=True)

    def __init__(self, post_id: int, tag_id: int) -> None:
        self.post_id = post_id
        self.tag_id = tag_id


class Post(Base):
    __tablename__ = 'post'

    SAFETY_SAFE = 'safe'
    SAFETY_SKETCHY = 'sketchy'
    SAFETY_UNSAFE = 'unsafe'

    TYPE_IMAGE = 'image'
    TYPE_ANIMATION = 'animation'
    TYPE_VIDEO = 'video'
    TYPE_FLASH = 'flash'

    FLAG_LOOP = 'loop'

    # basic meta
    post_id = Column('id', Integer, primary_key=True)
    user_id = Column(
        'user_id',
        Integer,
        ForeignKey('user.id', ondelete='SET NULL'),
        nullable=True,
        index=True)
    version = Column('version', Integer, default=1, nullable=False)
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_edit_time = Column('last_edit_time', DateTime)
    safety = Column('safety', Unicode(32), nullable=False)
    source = Column('source', Unicode(200))
    flags = Column('flags', PickleType, default=None)

    # content description
    type = Column('type', Unicode(32), nullable=False)
    checksum = Column('checksum', Unicode(64), nullable=False)
    file_size = Column('file_size', Integer)
    canvas_width = Column('image_width', Integer)
    canvas_height = Column('image_height', Integer)
    mime_type = Column('mime-type', Unicode(32), nullable=False)

    # foreign tables
    user = relationship('User')
    tags = relationship('Tag', backref='posts', secondary='post_tag')
    relations = relationship(
        'Post',
        secondary='post_relation',
        primaryjoin=post_id == PostRelation.parent_id,
        secondaryjoin=post_id == PostRelation.child_id, lazy='joined',
        backref='related_by')
    features = relationship(
        'PostFeature', cascade='all, delete-orphan', lazy='joined')
    scores = relationship(
        'PostScore', cascade='all, delete-orphan', lazy='joined')
    favorited_by = relationship(
        'PostFavorite', cascade='all, delete-orphan', lazy='joined')
    notes = relationship(
        'PostNote', cascade='all, delete-orphan', lazy='joined')
    comments = relationship('Comment', cascade='all, delete-orphan')

    # dynamic columns
    tag_count = column_property(
        select([func.count(PostTag.tag_id)])
        .where(PostTag.post_id == post_id)
        .correlate_except(PostTag))

    canvas_area = column_property(canvas_width * canvas_height)
    canvas_aspect_ratio = column_property(canvas_width / canvas_height)

    @property
    def is_featured(self) -> bool:
        featured_post = object_session(self) \
            .query(PostFeature) \
            .order_by(PostFeature.time.desc()) \
            .first()
        return featured_post and featured_post.post_id == self.post_id

    score = column_property(
        select([func.coalesce(func.sum(PostScore.score), 0)])
        .where(PostScore.post_id == post_id)
        .correlate_except(PostScore))

    favorite_count = column_property(
        select([func.count(PostFavorite.post_id)])
        .where(PostFavorite.post_id == post_id)
        .correlate_except(PostFavorite))

    last_favorite_time = column_property(
        select([func.max(PostFavorite.time)])
        .where(PostFavorite.post_id == post_id)
        .correlate_except(PostFavorite))

    feature_count = column_property(
        select([func.count(PostFeature.post_id)])
        .where(PostFeature.post_id == post_id)
        .correlate_except(PostFeature))

    last_feature_time = column_property(
        select([func.max(PostFeature.time)])
        .where(PostFeature.post_id == post_id)
        .correlate_except(PostFeature))

    comment_count = column_property(
        select([func.count(Comment.post_id)])
        .where(Comment.post_id == post_id)
        .correlate_except(Comment))

    last_comment_creation_time = column_property(
        select([func.max(Comment.creation_time)])
        .where(Comment.post_id == post_id)
        .correlate_except(Comment))

    last_comment_edit_time = column_property(
        select([func.max(Comment.last_edit_time)])
        .where(Comment.post_id == post_id)
        .correlate_except(Comment))

    note_count = column_property(
        select([func.count(PostNote.post_id)])
        .where(PostNote.post_id == post_id)
        .correlate_except(PostNote))

    relation_count = column_property(
        select([func.count(PostRelation.child_id)])
        .where(
            (PostRelation.parent_id == post_id) |
            (PostRelation.child_id == post_id))
        .correlate_except(PostRelation))

    __mapper_args__ = {
        'version_id_col': version,
        'version_id_generator': False,
    }
