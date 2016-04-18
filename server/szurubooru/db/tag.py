from sqlalchemy import Column, Integer, DateTime, String, ForeignKey, table
from sqlalchemy.orm import relationship, column_property
from sqlalchemy.sql.expression import func, select
from szurubooru.db.base import Base
from szurubooru.db.post import PostTag

class TagSuggestion(Base):
    __tablename__ = 'tag_suggestion'

    parent_id = Column('parent_id', Integer, ForeignKey('tag.id'), primary_key=True)
    child_id = Column('child_id', Integer, ForeignKey('tag.id'), primary_key=True)

    def __init__(self, parent_id, child_id):
        self.parent_id = parent_id
        self.child_id = child_id

class TagImplication(Base):
    __tablename__ = 'tag_implication'

    parent_id = Column('parent_id', Integer, ForeignKey('tag.id'), primary_key=True)
    child_id = Column('child_id', Integer, ForeignKey('tag.id'), primary_key=True)

    def __init__(self, parent_id, child_id):
        self.parent_id = parent_id
        self.child_id = child_id

class TagName(Base):
    __tablename__ = 'tag_name'

    tag_name_id = Column('tag_name_id', Integer, primary_key=True)
    tag_id = Column('tag_id', Integer, ForeignKey('tag.id'))
    name = Column('name', String(64), nullable=False, unique=True)

    def __init__(self, name):
        self.name = name

class Tag(Base):
    __tablename__ = 'tag'

    tag_id = Column('id', Integer, primary_key=True)
    category = Column('category', String(32), nullable=False)
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_edit_time = Column('last_edit_time', DateTime)

    names = relationship('TagName', cascade='all, delete-orphan')
    suggestions = relationship(
        'Tag',
        secondary='tag_suggestion',
        primaryjoin=tag_id == TagSuggestion.parent_id,
        secondaryjoin=tag_id == TagSuggestion.child_id)
    implications = relationship(
        'Tag',
        secondary='tag_implication',
        primaryjoin=tag_id == TagImplication.parent_id,
        secondaryjoin=tag_id == TagImplication.child_id)

    post_count = column_property(
        select([func.count('Post.post_id')]) \
        .where(PostTag.tag_id == tag_id) \
        .correlate(table('Tag')))

    first_name = column_property(
        select([TagName.name]) \
            .where(TagName.tag_id == tag_id) \
            .limit(1) \
            .as_scalar(),
        deferred=True)

    suggestion_count = column_property(
        select([func.count(TagSuggestion.child_id)]) \
            .where(TagSuggestion.parent_id == tag_id) \
            .as_scalar(),
        deferred=True)

    implication_count = column_property(
        select([func.count(TagImplication.child_id)]) \
            .where(TagImplication.parent_id == tag_id) \
            .as_scalar(),
        deferred=True)
