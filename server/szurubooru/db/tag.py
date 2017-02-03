from sqlalchemy import (
    Column, Integer, DateTime, Unicode, UnicodeText, ForeignKey)
from sqlalchemy.orm import relationship, column_property
from sqlalchemy.sql.expression import func, select
from szurubooru.db.base import Base
from szurubooru.db.post import PostTag


class TagSuggestion(Base):
    __tablename__ = 'tag_suggestion'

    parent_id = Column(
        'parent_id',
        Integer,
        ForeignKey('tag.id'),
        nullable=False,
        primary_key=True,
        index=True)
    child_id = Column(
        'child_id',
        Integer,
        ForeignKey('tag.id'),
        nullable=False,
        primary_key=True,
        index=True)

    def __init__(self, parent_id, child_id):
        self.parent_id = parent_id
        self.child_id = child_id


class TagImplication(Base):
    __tablename__ = 'tag_implication'

    parent_id = Column(
        'parent_id',
        Integer,
        ForeignKey('tag.id'),
        nullable=False,
        primary_key=True,
        index=True)
    child_id = Column(
        'child_id',
        Integer,
        ForeignKey('tag.id'),
        nullable=False,
        primary_key=True,
        index=True)

    def __init__(self, parent_id, child_id):
        self.parent_id = parent_id
        self.child_id = child_id


class TagName(Base):
    __tablename__ = 'tag_name'

    tag_name_id = Column('tag_name_id', Integer, primary_key=True)
    tag_id = Column(
        'tag_id', Integer, ForeignKey('tag.id'), nullable=False, index=True)
    name = Column('name', Unicode(64), nullable=False, unique=True)
    order = Column('ord', Integer, nullable=False, index=True)

    def __init__(self, name, order):
        self.name = name
        self.order = order


class Tag(Base):
    __tablename__ = 'tag'

    tag_id = Column('id', Integer, primary_key=True)
    category_id = Column(
        'category_id',
        Integer,
        ForeignKey('tag_category.id'),
        nullable=False,
        index=True)
    version = Column('version', Integer, default=1, nullable=False)
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_edit_time = Column('last_edit_time', DateTime)
    description = Column('description', UnicodeText, default=None)

    category = relationship('TagCategory', lazy='joined')
    names = relationship(
        'TagName',
        cascade='all,delete-orphan',
        lazy='joined',
        order_by='TagName.order')
    suggestions = relationship(
        'Tag',
        secondary='tag_suggestion',
        primaryjoin=tag_id == TagSuggestion.parent_id,
        secondaryjoin=tag_id == TagSuggestion.child_id,
        lazy='joined')
    implications = relationship(
        'Tag',
        secondary='tag_implication',
        primaryjoin=tag_id == TagImplication.parent_id,
        secondaryjoin=tag_id == TagImplication.child_id,
        lazy='joined')

    post_count = column_property(
        select([func.count(PostTag.post_id)])
        .where(PostTag.tag_id == tag_id)
        .correlate_except(PostTag))

    first_name = column_property(
        (
            select([TagName.name])
            .where(TagName.tag_id == tag_id)
            .order_by(TagName.order)
            .limit(1)
            .as_scalar()
        ),
        deferred=True)

    suggestion_count = column_property(
        (
            select([func.count(TagSuggestion.child_id)])
            .where(TagSuggestion.parent_id == tag_id)
            .as_scalar()
        ),
        deferred=True)

    implication_count = column_property(
        (
            select([func.count(TagImplication.child_id)])
            .where(TagImplication.parent_id == tag_id)
            .as_scalar()
        ),
        deferred=True)

    __mapper_args__ = {
        'version_id_col': version,
        'version_id_generator': False,
    }
