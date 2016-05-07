from sqlalchemy import Column, Integer, DateTime, String, Text, PickleType, ForeignKey
from sqlalchemy.orm import relationship, column_property, object_session
from sqlalchemy.sql.expression import func, select
from szurubooru.db.base import Base
from szurubooru.db.comment import Comment

class PostFeature(Base):
    __tablename__ = 'post_feature'

    post_feature_id = Column('id', Integer, primary_key=True)
    post_id = Column('post_id', Integer, ForeignKey('post.id'), nullable=False)
    user_id = Column('user_id', Integer, ForeignKey('user.id'), nullable=False)
    time = Column('time', DateTime, nullable=False)

    post = relationship('Post')
    user = relationship('User')

class PostScore(Base):
    __tablename__ = 'post_score'

    post_id = Column('post_id', Integer, ForeignKey('post.id'), primary_key=True)
    user_id = Column('user_id', Integer, ForeignKey('user.id'), primary_key=True)
    time = Column('time', DateTime, nullable=False)
    score = Column('score', Integer, nullable=False)

    post = relationship('Post')
    user = relationship('User')

class PostFavorite(Base):
    __tablename__ = 'post_favorite'

    post_id = Column('post_id', Integer, ForeignKey('post.id'), primary_key=True)
    user_id = Column('user_id', Integer, ForeignKey('user.id'), primary_key=True)
    time = Column('time', DateTime, nullable=False)

    post = relationship('Post')
    user = relationship('User')

class PostNote(Base):
    __tablename__ = 'post_note'

    post_note_id = Column('id', Integer, primary_key=True)
    post_id = Column('post_id', Integer, ForeignKey('post.id'), nullable=False)
    polygon = Column('polygon', PickleType, nullable=False)
    text = Column('text', Text, nullable=False)

    post = relationship('Post')

class PostRelation(Base):
    __tablename__ = 'post_relation'

    parent_id = Column('parent_id', Integer, ForeignKey('post.id'), primary_key=True)
    child_id = Column('child_id', Integer, ForeignKey('post.id'), primary_key=True)

    def __init__(self, parent_id, child_id):
        self.parent_id = parent_id
        self.child_id = child_id

class PostTag(Base):
    __tablename__ = 'post_tag'

    post_id = Column('post_id', Integer, ForeignKey('post.id'), primary_key=True)
    tag_id = Column('tag_id', Integer, ForeignKey('tag.id'), primary_key=True)

    def __init__(self, tag_id, post_id):
        self.tag_id = tag_id
        self.post_id = post_id

class Post(Base):
    __tablename__ = 'post'

    SAFETY_SAFE = 'safe'
    SAFETY_SKETCHY = 'sketchy'
    SAFETY_UNSAFE = 'unsafe'
    TYPE_IMAGE = 'image'
    TYPE_ANIMATION = 'animation'
    TYPE_VIDEO = 'video'
    TYPE_FLASH = 'flash'

    # basic meta
    post_id = Column('id', Integer, primary_key=True)
    user_id = Column('user_id', Integer, ForeignKey('user.id'))
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_edit_time = Column('last_edit_time', DateTime)
    safety = Column('safety', String(32), nullable=False)
    source = Column('source', String(200))
    flags = Column('flags', PickleType, default=None)

    # content description
    type = Column('type', String(32), nullable=False)
    checksum = Column('checksum', String(64), nullable=False)
    file_size = Column('file_size', Integer)
    canvas_width = Column('image_width', Integer)
    canvas_height = Column('image_height', Integer)
    mime_type = Column('mime-type', String(32), nullable=False)

    # foreign tables
    user = relationship('User')
    tags = relationship('Tag', backref='posts', secondary='post_tag')
    relations = relationship(
        'Post',
        secondary='post_relation',
        primaryjoin=post_id == PostRelation.parent_id,
        secondaryjoin=post_id == PostRelation.child_id)
    features = relationship(
        'PostFeature', cascade='all, delete-orphan', lazy='joined')
    scores = relationship(
        'PostScore', cascade='all, delete-orphan', lazy='joined')
    favorited_by = relationship(
        'PostFavorite', cascade='all, delete-orphan', lazy='joined')
    notes = relationship(
        'PostNote', cascade='all, delete-orphan', lazy='joined')
    comments = relationship('Comment')

    # dynamic columns
    tag_count = column_property(
        select([func.count(PostTag.tag_id)]) \
        .where(PostTag.post_id == post_id) \
        .correlate_except(PostTag))

    canvas_area = column_property(canvas_width * canvas_height)

    @property
    def is_featured(self):
        featured_post = object_session(self) \
            .query(PostFeature) \
            .order_by(PostFeature.time.desc()) \
            .first()
        return featured_post and featured_post.post_id == self.post_id

    score = column_property(
        select([func.coalesce(func.sum(PostScore.score), 0)]) \
        .where(PostScore.post_id == post_id) \
        .correlate_except(PostScore))

    favorite_count = column_property(
        select([func.count(PostFavorite.post_id)]) \
        .where(PostFavorite.post_id == post_id) \
        .correlate_except(PostFavorite))

    last_favorite_time = column_property(
        select([func.max(PostFavorite.time)]) \
        .where(PostFavorite.post_id == post_id) \
        .correlate_except(PostFavorite))

    feature_count = column_property(
        select([func.count(PostFeature.post_id)]) \
        .where(PostFeature.post_id == post_id) \
        .correlate_except(PostFeature))

    last_feature_time = column_property(
        select([func.max(PostFeature.time)]) \
        .where(PostFeature.post_id == post_id) \
        .correlate_except(PostFeature))

    comment_count = column_property(
        select([func.count(Comment.post_id)]) \
        .where(Comment.post_id == post_id) \
        .correlate_except(Comment))

    last_comment_creation_time = column_property(
        select([func.max(Comment.creation_time)]) \
        .where(Comment.post_id == post_id) \
        .correlate_except(Comment))

    last_comment_edit_time = column_property(
        select([func.max(Comment.last_edit_time)]) \
        .where(Comment.post_id == post_id) \
        .correlate_except(Comment))

    note_count = column_property(
        select([func.count(PostNote.post_id)]) \
        .where(PostNote.post_id == post_id) \
        .correlate_except(PostNote))
