"""
Change flags column type

Revision ID: 84bd402f15f0
Created at: 2016-04-22 20:48:32.386159
"""

import sqlalchemy as sa
from alembic import op

revision = "84bd402f15f0"
down_revision = "9587de88a84b"
branch_labels = None
depends_on = None


def upgrade():
    op.drop_column("post", "flags")
    op.add_column("post", sa.Column("flags", sa.PickleType(), nullable=True))


def downgrade():
    op.drop_column("post", "flags")
    op.add_column(
        "post",
        sa.Column("flags", sa.Integer(), autoincrement=False, nullable=False),
    )
