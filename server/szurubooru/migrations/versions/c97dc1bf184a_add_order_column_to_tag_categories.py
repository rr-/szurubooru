"""
Add order column to tag categories.

Revision ID: c97dc1bf184a
Created at: 2020-09-19 17:08:03.225667
"""

import sqlalchemy as sa
from alembic import op

revision = "c97dc1bf184a"
down_revision = "54de8acc6cef"
branch_labels = None
depends_on = None


def upgrade():
    op.add_column(
        "tag_category", sa.Column("order", sa.Integer, nullable=True)
    )
    op.execute(
        sa.table("tag_category", sa.column("order")).update().values(order=1)
    )
    op.alter_column("tag_category", "order", nullable=False)


def downgrade():
    op.drop_column("tag_category", "order")
