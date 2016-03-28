'''
Add unique constraint to the user name

Revision ID: d186d2e9c2c9
Created at: 2016-03-28 10:21:30.440333
'''

import sqlalchemy as sa
from alembic import op

revision = 'd186d2e9c2c9'
down_revision = 'e5c1216a8503'
branch_labels = None
depends_on = None

def upgrade():
    op.create_unique_constraint('uq_user_name', 'user', ['name'])

def downgrade():
    op.drop_constraint('uq_user_name', 'user', type_='unique')
