"""
Delete post columns

Revision ID: ed6dd16a30f3
Created at: 2016-04-24 16:29:25.309154
"""

import sqlalchemy as sa
from alembic import op

revision = "ed6dd16a30f3"
down_revision = "46df355634dc"
branch_labels = None
depends_on = None


def upgrade():
    for column_name in [
        "auto_comment_edit_time",
        "auto_fav_count",
        "auto_comment_creation_time",
        "auto_feature_count",
        "auto_comment_count",
        "auto_score",
        "auto_fav_time",
        "auto_feature_time",
        "auto_note_count",
    ]:
        op.drop_column("post", column_name)


def downgrade():
    for column_name in [
        "auto_note_count",
        "auto_feature_time",
        "auto_fav_time",
        "auto_score",
        "auto_comment_count",
        "auto_feature_count",
        "auto_comment_creation_time",
        "auto_fav_count",
        "auto_comment_edit_time",
    ]:
        op.add_column(
            "post",
            sa.Column(
                column_name, sa.INTEGER(), autoincrement=False, nullable=False
            ),
        )
