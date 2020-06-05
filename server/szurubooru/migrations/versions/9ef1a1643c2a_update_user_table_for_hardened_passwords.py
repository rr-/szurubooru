"""
Alter the password_hash field to work with larger output.
Particularly libsodium output for greater password security.

Revision ID: 9ef1a1643c2a
Created at: 2018-02-24 23:00:32.848575
"""

import sqlalchemy as sa
import sqlalchemy.ext.declarative
import sqlalchemy.orm.session
from alembic import op

revision = "9ef1a1643c2a"
down_revision = "02ef5f73f4ab"
branch_labels = None
depends_on = None

Base = sa.ext.declarative.declarative_base()


class User(Base):
    __tablename__ = "user"

    AVATAR_GRAVATAR = "gravatar"

    user_id = sa.Column("id", sa.Integer, primary_key=True)
    creation_time = sa.Column("creation_time", sa.DateTime, nullable=False)
    last_login_time = sa.Column("last_login_time", sa.DateTime)
    version = sa.Column("version", sa.Integer, default=1, nullable=False)
    name = sa.Column("name", sa.Unicode(50), nullable=False, unique=True)
    password_hash = sa.Column("password_hash", sa.Unicode(128), nullable=False)
    password_salt = sa.Column("password_salt", sa.Unicode(32))
    password_revision = sa.Column(
        "password_revision", sa.SmallInteger, default=0, nullable=False
    )
    email = sa.Column("email", sa.Unicode(64), nullable=True)
    rank = sa.Column("rank", sa.Unicode(32), nullable=False)
    avatar_style = sa.Column(
        "avatar_style", sa.Unicode(32), nullable=False, default=AVATAR_GRAVATAR
    )

    __mapper_args__ = {
        "version_id_col": version,
        "version_id_generator": False,
    }


def upgrade():
    op.alter_column(
        "user",
        "password_hash",
        existing_type=sa.VARCHAR(length=64),
        type_=sa.Unicode(length=128),
        existing_nullable=False,
    )
    op.add_column(
        "user",
        sa.Column(
            "password_revision", sa.SmallInteger(), nullable=True, default=0
        ),
    )

    session = sa.orm.session.Session(bind=op.get_bind())
    if session.query(User).count() >= 0:
        for user in session.query(User).all():
            password_hash_length = len(user.password_hash)
            if password_hash_length == 40:
                user.password_revision = 1
            elif password_hash_length == 64:
                user.password_revision = 2
            else:
                user.password_revision = 0
        session.flush()
    session.commit()

    op.alter_column(
        "user", "password_revision", existing_nullable=True, nullable=False
    )


def downgrade():
    op.alter_column(
        "user",
        "password_hash",
        existing_type=sa.Unicode(length=128),
        type_=sa.VARCHAR(length=64),
        existing_nullable=False,
    )
    op.drop_column("user", "password_revision")
