from sqlalchemy import Column, Integer, DateTime, String, ForeignKey
from sqlalchemy.orm import relationship
from szurubooru.db.base import Base

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

class Tag(Base):
    __tablename__ = 'tag'
    tag_id = Column('id', Integer, primary_key=True)
    category = Column('category', String(32), nullable=False)
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_edit_time = Column('last_edit_time', DateTime)
    post_count = Column('auto_post_count', Integer, nullable=False, default=0)
    names = relationship('TagName', cascade='all, delete-orphan')

    suggestions = relationship(
        TagSuggestion,
        backref='parent_tag',
        primaryjoin=tag_id == TagSuggestion.parent_id,
        cascade='all, delete-orphan')
    suggested_by = relationship(
        TagSuggestion,
        backref='child_tag',
        primaryjoin=tag_id == TagSuggestion.child_id,
        cascade='all, delete-orphan')

    implications = relationship(
        TagImplication,
        backref='parent_tag',
        primaryjoin=tag_id == TagImplication.parent_id,
        cascade='all, delete-orphan')
    implied_by = relationship(
        TagImplication,
        backref='child_tag',
        primaryjoin=tag_id == TagImplication.child_id,
        cascade='all, delete-orphan')

class TagName(Base):
    __tablename__ = 'tag_name'
    tag_name_id = Column('tag_name_id', Integer, primary_key=True)
    tag_id = Column('tag_id', Integer, ForeignKey('tag.id'))
    name = Column('name', String(64), nullable=False, unique=True)

    def __init__(self, name):
        self.name = name
