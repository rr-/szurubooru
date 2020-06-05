"""
Add entity versions

Revision ID: 7f6baf38c27c
Created at: 2016-08-06 22:26:58.111763
"""

import sqlalchemy as sa
from alembic import op

revision = "7f6baf38c27c"
down_revision = "4c526f869323"
branch_labels = None
depends_on = None

tables = ["tag_category", "tag", "user", "post", "comment"]


def upgrade():
    for table in tables:
        op.add_column(table, sa.Column("version", sa.Integer(), nullable=True))
        op.execute(
            sa.table(table, sa.column("version")).update().values(version=1)
        )
        op.alter_column(table, "version", nullable=False)


def downgrade():
    for table in tables:
        op.drop_column(table, "version")
