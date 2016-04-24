from sqlalchemy import Column, Integer, DateTime, Text, ForeignKey
from sqlalchemy.orm import relationship
from szurubooru.db.base import Base

class CommentScore(Base):
    __tablename__ = 'comment_score'

    comment_id = Column('comment_id', Integer, ForeignKey('comment.id'), primary_key=True)
    user_id = Column('user_id', Integer, ForeignKey('user.id'), primary_key=True)
    time = Column('time', DateTime, nullable=False)
    score = Column('score', Integer, nullable=False)

    comment = relationship('Comment')
    user = relationship('User')

class Comment(Base):
    __tablename__ = 'comment'

    comment_id = Column('id', Integer, primary_key=True)
    post_id = Column('post_id', Integer, ForeignKey('post.id'), nullable=False)
    user_id = Column('user_id', Integer, ForeignKey('user.id'))
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_edit_time = Column('last_edit_time', DateTime)
    text = Column('text', Text, default=None)

    user = relationship('User')
    post = relationship('Post')
    scores = relationship(
        'CommentScore', cascade='all, delete-orphan', lazy='joined')
