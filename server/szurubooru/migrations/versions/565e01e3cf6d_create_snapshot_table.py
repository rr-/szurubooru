"""
Create snapshot table

Revision ID: 565e01e3cf6d
Created at: 2016-04-19 12:07:58.372426
"""

import sqlalchemy as sa
from alembic import op

revision = "565e01e3cf6d"
down_revision = "336a76ec1338"
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        "snapshot",
        sa.Column("id", sa.Integer(), nullable=False),
        sa.Column("creation_time", sa.DateTime(), nullable=False),
        sa.Column("resource_type", sa.Unicode(length=32), nullable=False),
        sa.Column("resource_id", sa.Integer(), nullable=False),
        sa.Column("operation", sa.Unicode(length=16), nullable=False),
        sa.Column("user_id", sa.Integer(), nullable=True),
        sa.Column("data", sa.PickleType(), nullable=True),
        sa.ForeignKeyConstraint(["user_id"], ["user.id"]),
        sa.PrimaryKeyConstraint("id"),
    )


def downgrade():
    op.drop_table("snapshot")
