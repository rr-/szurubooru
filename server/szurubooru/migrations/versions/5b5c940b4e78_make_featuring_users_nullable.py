'''
make featuring users nullable

Revision ID: 5b5c940b4e78
Created at: 2025-04-04 08:24:39.000603
'''

import sqlalchemy as sa
from alembic import op

revision = '5b5c940b4e78'
down_revision = 'adcd63ff76a2'
branch_labels = None
depends_on = None

def upgrade():
    op.alter_column(
        "post_feature", "user_id", nullable=True, existing_nullable=False
    )
    pass

def downgrade():
    op.alter_column(
        "post_feature", "user_id", nullable=False, existing_nullable=True
    )
    pass
