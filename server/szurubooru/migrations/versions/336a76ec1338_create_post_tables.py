"""
Create post tables

Revision ID: 336a76ec1338
Created at: 2016-04-19 12:06:08.649503
"""

import sqlalchemy as sa
from alembic import op

revision = "336a76ec1338"
down_revision = "00cb3a2734db"
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        "post",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("user_id", sa.Integer(), nullable=True),
        sa.Column("creation_time", sa.DateTime(), nullable=False),
        sa.Column("last_edit_time", sa.DateTime(), nullable=True),
        sa.Column("safety", sa.Unicode(length=32), nullable=False),
        sa.Column("type", sa.Unicode(length=32), nullable=False),
        sa.Column("checksum", sa.Unicode(length=64), nullable=False),
        sa.Column("source", sa.Unicode(length=200), nullable=True),
        sa.Column("file_size", sa.Integer(), nullable=True),
        sa.Column("image_width", sa.Integer(), nullable=True),
        sa.Column("image_height", sa.Integer(), nullable=True),
        sa.Column("flags", sa.Integer(), nullable=False),
        sa.Column("auto_fav_count", sa.Integer(), nullable=False),
        sa.Column("auto_score", sa.Integer(), nullable=False),
        sa.Column("auto_feature_count", sa.Integer(), nullable=False),
        sa.Column("auto_comment_count", sa.Integer(), nullable=False),
        sa.Column("auto_note_count", sa.Integer(), nullable=False),
        sa.Column("auto_fav_time", sa.Integer(), nullable=False),
        sa.Column("auto_feature_time", sa.Integer(), nullable=False),
        sa.Column("auto_comment_creation_time", sa.Integer(), nullable=False),
        sa.Column("auto_comment_edit_time", sa.Integer(), nullable=False),
        sa.ForeignKeyConstraint(["user_id"], ["user.id"]),
        sa.PrimaryKeyConstraint("id"),
    )

    op.create_table(
        "post_relation",
        sa.Column("parent_id", sa.Integer(), nullable=False),
        sa.Column("child_id", sa.Integer(), nullable=False),
        sa.ForeignKeyConstraint(["child_id"], ["post.id"]),
        sa.ForeignKeyConstraint(["parent_id"], ["post.id"]),
        sa.PrimaryKeyConstraint("parent_id", "child_id"),
    )

    op.create_table(
        "post_tag",
        sa.Column("post_id", sa.Integer(), nullable=False),
        sa.Column("tag_id", sa.Integer(), nullable=False),
        sa.ForeignKeyConstraint(["post_id"], ["post.id"]),
        sa.ForeignKeyConstraint(["tag_id"], ["tag.id"]),
        sa.PrimaryKeyConstraint("post_id", "tag_id"),
    )


def downgrade():
    op.drop_table("post_tag")
    op.drop_table("post_relation")
    op.drop_table("post")
