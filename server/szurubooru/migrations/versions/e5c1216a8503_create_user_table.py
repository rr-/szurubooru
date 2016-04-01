'''
Create user table

Revision ID: e5c1216a8503
Created at: 2016-03-20 15:53:25.030415
'''

import sqlalchemy as sa
from alembic import op

revision = 'e5c1216a8503'
down_revision = None
branch_labels = None
depends_on = None

def upgrade():
    op.create_table(
        'user',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('name', sa.String(length=50), nullable=False),
        sa.Column('password_hash', sa.String(length=64), nullable=False),
        sa.Column('pasword_salt', sa.String(length=32), nullable=True),
        sa.Column('email', sa.String(length=200), nullable=True),
        sa.Column('access_rank', sa.Integer(), nullable=False),
        sa.Column('creation_time', sa.DateTime(), nullable=False),
        sa.Column('last_login_time', sa.DateTime(), nullable=False),
        sa.Column('avatar_style', sa.Integer(), nullable=False),
        sa.PrimaryKeyConstraint('id'))

def downgrade():
    op.drop_table('user')
