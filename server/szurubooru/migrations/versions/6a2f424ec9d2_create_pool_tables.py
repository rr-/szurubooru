"""
create pool tables

Revision ID: 6a2f424ec9d2
Created at: 2020-05-03 14:47:59.136410
"""

import sqlalchemy as sa
from alembic import op

revision = "6a2f424ec9d2"
down_revision = "1e280b5d5df1"
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        "pool_category",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("version", sa.Integer(), nullable=False, default=1),
        sa.Column("name", sa.Unicode(length=32), nullable=False),
        sa.Column("color", sa.Unicode(length=32), nullable=False),
        sa.Column("default", sa.Boolean(), nullable=False, default=False),
        sa.PrimaryKeyConstraint("id"),
    )

    op.create_table(
        "pool",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("version", sa.Integer(), nullable=False, default=1),
        sa.Column("description", sa.UnicodeText(), nullable=True),
        sa.Column("category_id", sa.Integer(), nullable=False),
        sa.Column("creation_time", sa.DateTime(), nullable=False),
        sa.Column("last_edit_time", sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(["category_id"], ["pool_category.id"]),
        sa.PrimaryKeyConstraint("id"),
    )

    op.create_table(
        "pool_name",
        sa.Column("pool_name_id", sa.Integer(), nullable=False),
        sa.Column("pool_id", sa.Integer(), nullable=False),
        sa.Column("name", sa.Unicode(length=256), nullable=False),
        sa.Column("ord", sa.Integer(), nullable=False, index=True),
        sa.ForeignKeyConstraint(["pool_id"], ["pool.id"]),
        sa.PrimaryKeyConstraint("pool_name_id"),
        sa.UniqueConstraint("name"),
    )

    op.create_table(
        "pool_post",
        sa.Column("pool_id", sa.Integer(), nullable=False),
        sa.Column("post_id", sa.Integer(), nullable=False, index=True),
        sa.Column("ord", sa.Integer(), nullable=False, index=True),
        sa.ForeignKeyConstraint(["pool_id"], ["pool.id"], ondelete="CASCADE"),
        sa.ForeignKeyConstraint(["post_id"], ["post.id"], ondelete="CASCADE"),
        sa.PrimaryKeyConstraint("pool_id", "post_id"),
    )


def downgrade():
    op.drop_index(op.f("ix_pool_name_ord"), table_name="pool_name")
    op.drop_table("pool_post")
    op.drop_table("pool_name")
    op.drop_table("pool")
    op.drop_table("pool_category")
