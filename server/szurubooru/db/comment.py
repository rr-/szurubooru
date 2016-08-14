from sqlalchemy import Column, Integer, DateTime, UnicodeText, ForeignKey
from sqlalchemy.orm import relationship, backref
from sqlalchemy.sql.expression import func
from szurubooru.db.base import Base


class CommentScore(Base):
    __tablename__ = 'comment_score'

    comment_id = Column(
        'comment_id', Integer, ForeignKey('comment.id'), primary_key=True)
    user_id = Column(
        'user_id',
        Integer,
        ForeignKey('user.id'),
        primary_key=True,
        index=True)
    time = Column('time', DateTime, nullable=False)
    score = Column('score', Integer, nullable=False)

    comment = relationship('Comment')
    user = relationship(
        'User',
        backref=backref('comment_scores', cascade='all, delete-orphan'))


class Comment(Base):
    __tablename__ = 'comment'

    comment_id = Column('id', Integer, primary_key=True)
    post_id = Column(
        'post_id', Integer, ForeignKey('post.id'), index=True, nullable=False)
    user_id = Column('user_id', Integer, ForeignKey('user.id'), index=True)
    version = Column('version', Integer, default=1, nullable=False)
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_edit_time = Column('last_edit_time', DateTime)
    text = Column('text', UnicodeText, default=None)

    user = relationship('User')
    post = relationship('Post')
    scores = relationship(
        'CommentScore', cascade='all, delete-orphan', lazy='joined')

    @property
    def score(self):
        from szurubooru.db import session
        return session \
            .query(func.sum(CommentScore.score)) \
            .filter(CommentScore.comment_id == self.comment_id) \
            .one()[0] or 0

    __mapper_args__ = {
        'version_id_col': version,
        'version_id_generator': False,
    }
