"""
Create user table

Revision ID: e5c1216a8503
Created at: 2016-03-20 15:53:25.030415
"""

import sqlalchemy as sa
from alembic import op

revision = "e5c1216a8503"
down_revision = None
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        "user",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("name", sa.Unicode(length=50), nullable=False),
        sa.Column("password_hash", sa.Unicode(length=64), nullable=False),
        sa.Column("password_salt", sa.Unicode(length=32), nullable=True),
        sa.Column("email", sa.Unicode(length=64), nullable=True),
        sa.Column("rank", sa.Unicode(length=32), nullable=False),
        sa.Column("creation_time", sa.DateTime(), nullable=False),
        sa.Column("last_login_time", sa.DateTime()),
        sa.Column("avatar_style", sa.Unicode(length=32), nullable=False),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_unique_constraint("uq_user_name", "user", ["name"])


def downgrade():
    op.drop_table("user")
