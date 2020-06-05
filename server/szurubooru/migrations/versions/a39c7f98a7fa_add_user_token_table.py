"""
Added a user_token table for API authorization

Revision ID: a39c7f98a7fa
Created at: 2018-02-25 01:31:27.345595
"""

import sqlalchemy as sa
from alembic import op

revision = "a39c7f98a7fa"
down_revision = "9ef1a1643c2a"
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        "user_token",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("user_id", sa.Integer(), nullable=False),
        sa.Column("token", sa.Unicode(length=36), nullable=False),
        sa.Column("note", sa.Unicode(length=128), nullable=True),
        sa.Column("enabled", sa.Boolean(), nullable=False),
        sa.Column("expiration_time", sa.DateTime(), nullable=True),
        sa.Column("creation_time", sa.DateTime(), nullable=False),
        sa.Column("last_edit_time", sa.DateTime(), nullable=True),
        sa.Column("last_usage_time", sa.DateTime(), nullable=True),
        sa.Column("version", sa.Integer(), nullable=False),
        sa.ForeignKeyConstraint(["user_id"], ["user.id"], ondelete="CASCADE"),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_index(
        op.f("ix_user_token_user_id"), "user_token", ["user_id"], unique=False
    )


def downgrade():
    op.drop_index(op.f("ix_user_token_user_id"), table_name="user_token")
    op.drop_table("user_token")
