"""
Change flags column to string

Revision ID: 1cd4c7b22846
Created at: 2018-09-21 19:37:27.686568
"""

import sqlalchemy as sa
from alembic import op

revision = "1cd4c7b22846"
down_revision = "a39c7f98a7fa"
branch_labels = None
depends_on = None


def upgrade():
    conn = op.get_bind()
    op.alter_column("post", "flags", new_column_name="oldflags")
    op.add_column(
        "post", sa.Column("flags", sa.Unicode(200), default="", nullable=True)
    )
    posts = sa.Table(
        "post",
        sa.MetaData(),
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("flags", sa.Unicode(200), default="", nullable=True),
        sa.Column("oldflags", sa.PickleType(), nullable=True),
    )
    for row in conn.execute(posts.select()):
        newflag = ",".join(row.oldflags) if row.oldflags else ""
        conn.execute(
            posts.update().where(posts.c.id == row.id).values(flags=newflag)
        )
    op.drop_column("post", "oldflags")


def downgrade():
    conn = op.get_bind()
    op.alter_column("post", "flags", new_column_name="oldflags")
    op.add_column("post", sa.Column("flags", sa.PickleType(), nullable=True))
    posts = sa.Table(
        "post",
        sa.MetaData(),
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("flags", sa.PickleType(), nullable=True),
        sa.Column("oldflags", sa.Unicode(200), default="", nullable=True),
    )
    for row in conn.execute(posts.select()):
        newflag = [x for x in row.oldflags.split(",") if x]
        conn.execute(
            posts.update().where(posts.c.id == row.id).values(flags=newflag)
        )
    op.drop_column("post", "oldflags")
