"""
Add MD5 checksums to posts

Revision ID: adcd63ff76a2
Created at: 2021-01-05 17:08:21.741601
"""

import sqlalchemy as sa
from alembic import op

revision = "adcd63ff76a2"
down_revision = "c867abb456b1"
branch_labels = None
depends_on = None


def upgrade():
    op.add_column("post", sa.Column("checksum_md5", sa.Unicode(32)))


def downgrade():
    op.drop_column("post", "checksum_md5")
