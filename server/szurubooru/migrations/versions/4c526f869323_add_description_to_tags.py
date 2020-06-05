"""
Add description to tags

Revision ID: 4c526f869323
Created at: 2016-06-21 17:56:34.979741
"""

import sqlalchemy as sa
from alembic import op

revision = "4c526f869323"
down_revision = "055d0e048fb3"
branch_labels = None
depends_on = None


def upgrade():
    op.add_column(
        "tag", sa.Column("description", sa.UnicodeText(), nullable=True)
    )


def downgrade():
    op.drop_column("tag", "description")
