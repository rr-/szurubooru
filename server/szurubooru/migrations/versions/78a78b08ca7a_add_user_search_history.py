'''
add user search history

Revision ID: 78a78b08ca7a
Created at: 2026-06-29 12:07:53.780064
'''

import sqlalchemy as sa
from alembic import op



revision = '78a78b08ca7a'
down_revision = '5b5c940b4e78'
branch_labels = None
depends_on = None

def upgrade():
    op.add_column("user", sa.Column("search_history", sa.Text, nullable=True))

def downgrade():
    op.drop_column("user", "search_history")
