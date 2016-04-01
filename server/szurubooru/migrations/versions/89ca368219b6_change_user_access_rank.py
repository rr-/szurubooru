'''
Changes access rank column to string

Revision ID: 89ca368219b6
Created at: 2016-03-28 10:35:40.285485
'''

import sqlalchemy as sa
from alembic import op

revision = '89ca368219b6'
down_revision = 'd186d2e9c2c9'
branch_labels = None
depends_on = None

def upgrade():
    op.drop_column('user', 'access_rank')
    op.add_column('user', sa.Column('access_rank', sa.String(length=32), nullable=False))

def downgrade():
    op.drop_column('user', 'access_rank')
    op.add_column('user', sa.Column('access_rank', sa.INTEGER(), autoincrement=False, nullable=False))
