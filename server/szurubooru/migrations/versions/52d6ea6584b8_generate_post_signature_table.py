"""
Generate post signature table

Revision ID: 52d6ea6584b8
Created at: 2020-03-07 17:03:40.193512
"""

import sqlalchemy as sa
from alembic import op

revision = "52d6ea6584b8"
down_revision = "3c1f0316fa7f"
branch_labels = None
depends_on = None


def upgrade():
    ArrayType = sa.dialects.postgresql.ARRAY(sa.Integer, dimensions=1)
    op.create_table(
        "post_signature",
        sa.Column("post_id", sa.Integer(), nullable=False),
        sa.Column("signature", sa.LargeBinary(), nullable=False),
        sa.Column("words", ArrayType, nullable=False),
        sa.ForeignKeyConstraint(["post_id"], ["post.id"]),
        sa.PrimaryKeyConstraint("post_id"),
    )


def downgrade():
    op.drop_table("post_signature")
