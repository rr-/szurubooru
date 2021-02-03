"""
add_description_to_post

Revision ID: 58bba7e0c554
Created at: 2021-01-30 18:06:11.511449
"""

import sqlalchemy as sa
from alembic import op

revision = "58bba7e0c554"
down_revision = "adcd63ff76a2"
branch_labels = None
depends_on = None


def upgrade():
    op.add_column(
        "post", sa.Column("description", sa.UnicodeText(), nullable=True)
    )


def downgrade():
    op.drop_column("post", "description")
