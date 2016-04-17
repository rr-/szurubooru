from sqlalchemy import Column, Integer, DateTime, String, ForeignKey
from sqlalchemy.orm import relationship, column_property
from sqlalchemy.sql.expression import func, select
from szurubooru.db.base import Base

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
    TYPE_IMAGE = 'anim'
    TYPE_ANIMATION = 'anim'
    TYPE_FLASH = 'flash'
    TYPE_VIDEO = 'video'
    TYPE_YOUTUBE = 'youtube'
    FLAG_LOOP_VIDEO = 1

    post_id = Column('id', Integer, primary_key=True)
    user_id = Column('user_id', Integer, ForeignKey('user.id'))
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_edit_time = Column('last_edit_time', DateTime)
    safety = Column('safety', String(32), nullable=False)
    type = Column('type', String(32), nullable=False)
    checksum = Column('checksum', String(64), nullable=False)
    source = Column('source', String(200))
    file_size = Column('file_size', Integer)
    image_width = Column('image_width', Integer)
    image_height = Column('image_height', Integer)
    flags = Column('flags', Integer, nullable=False, default=0)

    user = relationship('User')
    tags = relationship('Tag', backref='posts', secondary='post_tag')
    relations = relationship(
        'Post',
        secondary='post_relation',
        primaryjoin=post_id == PostRelation.parent_id,
        secondaryjoin=post_id == PostRelation.child_id)

    tag_count = column_property(
        select(
            [func.count('1')],
            PostTag.post_id == post_id
        ) \
        .correlate('Post') \
        .label('tag_count')
    )

    # TODO: wire these
    fav_count = Column('auto_fav_count', Integer, nullable=False, default=0)
    score = Column('auto_score', Integer, nullable=False, default=0)
    feature_count = Column('auto_feature_count', Integer, nullable=False, default=0)
    comment_count = Column('auto_comment_count', Integer, nullable=False, default=0)
    note_count = Column('auto_note_count', Integer, nullable=False, default=0)
    last_fav_time = Column(
        'auto_fav_time', Integer, nullable=False, default=0)
    last_feature_time = Column(
        'auto_feature_time', Integer, nullable=False, default=0)
    last_comment_edit_time = Column(
        'auto_comment_creation_time', Integer, nullable=False, default=0)
    last_comment_creation_time = Column(
        'auto_comment_edit_time', Integer, nullable=False, default=0)
