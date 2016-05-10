from sqlalchemy import Column, Integer, Unicode, DateTime
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
    RANK_NOBODY = 'nobody' # used for privileges: "nobody can be higher than admin"

    user_id = Column('id', Integer, primary_key=True)
    name = Column('name', Unicode(50), nullable=False, unique=True)
    password_hash = Column('password_hash', Unicode(64), nullable=False)
    password_salt = Column('password_salt', Unicode(32))
    email = Column('email', Unicode(64), nullable=True)
    rank = Column('rank', Unicode(32), nullable=False)
    creation_time = Column('creation_time', DateTime, nullable=False)
    last_login_time = Column('last_login_time', DateTime)
    avatar_style = Column(
        'avatar_style', Unicode(32), nullable=False, default=AVATAR_GRAVATAR)
