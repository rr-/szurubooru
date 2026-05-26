'''
Add blocklist related fields

add_blocklist

Revision ID: 9ba5e3a6ee7c
Created at: 2023-05-20 22:28:10.824954
'''

import sqlalchemy as sa
from alembic import op

revision = '9ba5e3a6ee7c'
down_revision = 'adcd63ff76a2'
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        "user_tag_blocklist",
        sa.Column("user_id", sa.Integer(), nullable=False),
        sa.Column("tag_id", sa.Integer(), nullable=False),
        sa.ForeignKeyConstraint(["user_id"], ["user.id"]),
        sa.ForeignKeyConstraint(["tag_id"], ["tag.id"]),
        sa.PrimaryKeyConstraint("user_id", "tag_id"),
    )

def downgrade():
    op.drop_table('user_tag_blocklist')
