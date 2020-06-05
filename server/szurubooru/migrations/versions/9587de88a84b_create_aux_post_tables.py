"""
Create auxilliary post tables

Revision ID: 9587de88a84b
Created at: 2016-04-22 17:42:57.697229
"""

import sqlalchemy as sa
from alembic import op

revision = "9587de88a84b"
down_revision = "46cd5229839b"
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        "post_favorite",
        sa.Column("post_id", sa.Integer(), nullable=False),
        sa.Column("user_id", sa.Integer(), nullable=False),
        sa.Column("time", sa.DateTime(), nullable=False),
        sa.ForeignKeyConstraint(["post_id"], ["post.id"]),
        sa.ForeignKeyConstraint(["user_id"], ["user.id"]),
        sa.PrimaryKeyConstraint("post_id", "user_id"),
    )

    op.create_table(
        "post_feature",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("post_id", sa.Integer(), nullable=False),
        sa.Column("user_id", sa.Integer(), nullable=False),
        sa.Column("time", sa.DateTime(), nullable=False),
        sa.ForeignKeyConstraint(["post_id"], ["post.id"]),
        sa.ForeignKeyConstraint(["user_id"], ["user.id"]),
        sa.PrimaryKeyConstraint("id"),
    )

    op.create_table(
        "post_note",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("post_id", sa.Integer(), nullable=False),
        sa.Column("text", sa.UnicodeText(), nullable=False),
        sa.Column("polygon", sa.PickleType(), nullable=False),
        sa.ForeignKeyConstraint(["post_id"], ["post.id"]),
        sa.PrimaryKeyConstraint("id"),
    )

    op.create_table(
        "post_score",
        sa.Column("post_id", sa.Integer(), nullable=False),
        sa.Column("user_id", sa.Integer(), nullable=False),
        sa.Column("time", sa.DateTime(), nullable=False),
        sa.Column("score", sa.Integer(), nullable=False),
        sa.ForeignKeyConstraint(["post_id"], ["post.id"]),
        sa.ForeignKeyConstraint(["user_id"], ["user.id"]),
        sa.PrimaryKeyConstraint("post_id", "user_id"),
    )


def downgrade():
    op.drop_table("post_score")
    op.drop_table("post_note")
    op.drop_table("post_feature")
    op.drop_table("post_favorite")
