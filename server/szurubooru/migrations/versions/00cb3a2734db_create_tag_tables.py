"""
Create tag tables

Revision ID: 00cb3a2734db
Created at: 2016-04-15 23:15:36.255429
"""

import sqlalchemy as sa
from alembic import op

revision = "00cb3a2734db"
down_revision = "e5c1216a8503"
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        "tag_category",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("name", sa.Unicode(length=32), nullable=False),
        sa.Column("color", sa.Unicode(length=32), nullable=False),
        sa.PrimaryKeyConstraint("id"),
    )

    op.create_table(
        "tag",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("category_id", sa.Integer(), nullable=False),
        sa.Column("creation_time", sa.DateTime(), nullable=False),
        sa.Column("last_edit_time", sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(["category_id"], ["tag_category.id"]),
        sa.PrimaryKeyConstraint("id"),
    )

    op.create_table(
        "tag_name",
        sa.Column("tag_name_id", sa.Integer(), nullable=False),
        sa.Column("tag_id", sa.Integer(), nullable=False),
        sa.Column("name", sa.Unicode(length=64), nullable=False),
        sa.ForeignKeyConstraint(["tag_id"], ["tag.id"]),
        sa.PrimaryKeyConstraint("tag_name_id"),
        sa.UniqueConstraint("name"),
    )

    op.create_table(
        "tag_implication",
        sa.Column("parent_id", sa.Integer(), nullable=False),
        sa.Column("child_id", sa.Integer(), nullable=False),
        sa.ForeignKeyConstraint(["parent_id"], ["tag.id"]),
        sa.ForeignKeyConstraint(["child_id"], ["tag.id"]),
        sa.PrimaryKeyConstraint("parent_id", "child_id"),
    )

    op.create_table(
        "tag_suggestion",
        sa.Column("parent_id", sa.Integer(), nullable=False),
        sa.Column("child_id", sa.Integer(), nullable=False),
        sa.ForeignKeyConstraint(["parent_id"], ["tag.id"]),
        sa.ForeignKeyConstraint(["child_id"], ["tag.id"]),
        sa.PrimaryKeyConstraint("parent_id", "child_id"),
    )


def downgrade():
    op.drop_table("tag_suggestion")
    op.drop_table("tag_implication")
    op.drop_table("tag_name")
    op.drop_table("tag")
    op.drop_table("tag_category")
