"""
Add snapshot resource_repr column

Revision ID: 46cd5229839b
Created at: 2016-04-21 19:00:48.087069
"""

import sqlalchemy as sa
from alembic import op

revision = "46cd5229839b"
down_revision = "565e01e3cf6d"
branch_labels = None
depends_on = None


def upgrade():
    op.add_column(
        "snapshot",
        sa.Column("resource_repr", sa.Unicode(length=64), nullable=False),
    )


def downgrade():
    op.drop_column("snapshot", "resource_repr")
