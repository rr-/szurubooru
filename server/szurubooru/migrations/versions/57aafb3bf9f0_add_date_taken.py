"""
add_date_taken

Revision ID: 57aafb3bf9f0
Created at: 2021-11-26 21:00:19.698012
"""

import sqlalchemy as sa
from alembic import op

revision = "57aafb3bf9f0"
down_revision = "adcd63ff76a2"
branch_labels = None
depends_on = None


def upgrade():
    op.add_column(
        "post", sa.Column("date_taken", sa.DateTime(), nullable=True)
    )


def downgrade():
    op.drop_column("post", "date_taken")
