"""
Add file last modified time

Revision ID: 46c358b0ca93
Created at: 2020-08-26 17:08:17.845827
"""

import sqlalchemy as sa
from alembic import op

revision = "46c358b0ca93"
down_revision = "54de8acc6cef"
branch_labels = None
depends_on = None


def upgrade():
    conn = op.get_bind()
    op.add_column(
        "post",
        sa.Column("file_last_modified_time", sa.DateTime(), nullable=True),
    )
    posts = sa.Table(
        "post",
        sa.MetaData(),
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("creation_time", sa.DateTime(), nullable=False),
        sa.Column("file_last_modified_time", sa.DateTime(), nullable=True),
    )
    for row in conn.execute(posts.select()):
        if row.file_last_modified_time is None:
            conn.execute(
                posts.update()
                .where(posts.c.id == row.id)
                .values(file_last_modified_time=row.creation_time)
            )

    op.alter_column("post", "file_last_modified_time", nullable=False)


def downgrade():
    op.drop_column("post", "file_last_modified_time")
