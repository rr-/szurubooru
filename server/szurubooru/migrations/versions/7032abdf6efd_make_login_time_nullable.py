'''
Make login time nullable

Revision ID: 7032abdf6efd
Created at: 2016-03-28 13:35:59.147167
'''

import sqlalchemy as sa
from alembic import op
from sqlalchemy.dialects import postgresql

revision = '7032abdf6efd'
down_revision = '89ca368219b6'
branch_labels = None
depends_on = None

def upgrade():
    op.alter_column(
        'user', 'last_login_time',
        existing_type=postgresql.TIMESTAMP(), nullable=True)

def downgrade():
    op.alter_column(
        'user', 'last_login_time',
        existing_type=postgresql.TIMESTAMP(), nullable=False)
