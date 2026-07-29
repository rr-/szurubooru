'''
add_oidc_subject_to_user

Revision ID: 516093f0ad52
Created at: 2026-06-24 08:05:21.791859
'''

import sqlalchemy as sa
from alembic import op



revision = '516093f0ad52'
down_revision = '5b5c940b4e78'
branch_labels = None
depends_on = None

def upgrade():
    op.add_column(
        "user",
        sa.Column("oidc_subject", sa.Unicode(length=256), nullable=True),
    )
    op.create_unique_constraint("uq_user_oidc_subject", "user", ["oidc_subject"])


def downgrade():
    op.drop_constraint("uq_user_oidc_subject", "user", type_="unique")
    op.drop_column("user", "oidc_subject")
