'''
create ban table

Revision ID: cc2956cb8ee7
Created at: 2023-05-12 02:04:22.592006
'''

import sqlalchemy as sa
from alembic import op



revision = 'cc2956cb8ee7'
down_revision = 'adcd63ff76a2'
branch_labels = None
depends_on = None

def upgrade():
    op.create_table(
        "post_ban",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("checksum", sa.Unicode(64), nullable=False),
        sa.Column("time", sa.DateTime, nullable=False),
        sa.PrimaryKeyConstraint("id")
    )
    op.create_unique_constraint("uq_ban_checksum", "post_ban", ["checksum"])

def downgrade():
    op.drop_table("post_ban")
