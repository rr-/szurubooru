'''
Add title and description to posts

Revision ID: 0c149800a728
Created at: 2024-12-03 08:21:21.113161
'''

import sqlalchemy as sa
from alembic import op



revision = '0c149800a728'
down_revision = 'adcd63ff76a2'
branch_labels = None
depends_on = None

def upgrade():
    op.add_column('post', sa.Column('title', sa.Unicode(length=512), nullable=False))
    op.add_column('post', sa.Column('description', sa.Unicode(length=2048), nullable=False))

def downgrade():
    op.drop_column('post', 'description')
    op.drop_column('post', 'title')
