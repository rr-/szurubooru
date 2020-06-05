"""
Add comment tables

Revision ID: 46df355634dc
Created at: 2016-04-24 09:02:05.008648
"""

import sqlalchemy as sa
from alembic import op

revision = "46df355634dc"
down_revision = "84bd402f15f0"
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        "comment",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("user_id", sa.Integer(), nullable=True),
        sa.Column("post_id", sa.Integer(), nullable=False),
        sa.Column("creation_time", sa.DateTime(), nullable=False),
        sa.Column("last_edit_time", sa.DateTime(), nullable=True),
        sa.Column("text", sa.UnicodeText(), nullable=True),
        sa.ForeignKeyConstraint(["user_id"], ["user.id"]),
        sa.ForeignKeyConstraint(["post_id"], ["post.id"]),
        sa.PrimaryKeyConstraint("id"),
    )

    op.create_table(
        "comment_score",
        sa.Column("comment_id", sa.Integer(), nullable=False),
        sa.Column("user_id", sa.Integer(), nullable=False),
        sa.Column("time", sa.DateTime(), nullable=False),
        sa.Column("score", sa.Integer(), nullable=False),
        sa.ForeignKeyConstraint(["comment_id"], ["comment.id"]),
        sa.ForeignKeyConstraint(["user_id"], ["user.id"]),
        sa.PrimaryKeyConstraint("comment_id", "user_id"),
    )


def downgrade():
    op.drop_table("comment_score")
    op.drop_table("comment")
