from sqlalchemy import Column, Integer, String, DateTime
from szurubooru.db.base import Base

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
    RANK_NOBODY = 'nobody'
    ALL_RANKS = [
        RANK_ANONYMOUS,
        RANK_RESTRICTED,
        RANK_REGULAR,
        RANK_POWER,
        RANK_MODERATOR,
        RANK_ADMINISTRATOR,
        RANK_NOBODY, # nobody can have higher privileges than administrator
    ]

    user_id = Column('id', Integer, primary_key=True)
    name = Column('name', String(50), nullable=False, unique=True)
    password_hash = Column('password_hash', String(64), nullable=False)
    password_salt = Column('password_salt', String(32))
    email = Column('email', String(64), nullable=True)
    rank = Column('rank', String(32), nullable=False)
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_login_time = Column('last_login_time', DateTime)
    avatar_style = Column(
        'avatar_style', String(32), nullable=False, default=AVATAR_GRAVATAR)
