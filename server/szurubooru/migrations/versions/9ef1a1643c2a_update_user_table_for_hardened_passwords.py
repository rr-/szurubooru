'''
Alter the password_hash field to work with larger output.
Particularly libsodium output for greater password security.

Revision ID: 9ef1a1643c2a
Created at: 2018-02-24 23:00:32.848575
'''

import sqlalchemy as sa
from alembic import op


revision = '9ef1a1643c2a'
down_revision = '02ef5f73f4ab'
branch_labels = None
depends_on = None


def upgrade():
    op.alter_column('user', 'password_hash',
                    existing_type=sa.VARCHAR(length=64),
                    type_=sa.Unicode(length=128),
                    existing_nullable=False)
    op.add_column('user', sa.Column('password_revision',
                                    sa.SmallInteger(),
                                    nullable=False))


def downgrade():
    op.alter_column('user', 'password_hash',
                    existing_type=sa.Unicode(length=128),
                    type_=sa.VARCHAR(length=64),
                    existing_nullable=False)
    op.drop_column('user', 'password_revision')
