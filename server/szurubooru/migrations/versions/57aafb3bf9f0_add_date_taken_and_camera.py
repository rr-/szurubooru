"""
Add camera and date_taken, and fill them out

Revision ID: 57aafb3bf9f0
Created at: 2022-01-07 17:54:15.982212
"""

import sqlalchemy as sa
from alembic import op

from szurubooru.func.files import _get_full_path
from szurubooru.func.images import Image, Video
from szurubooru.func.mime import get_extension
from szurubooru.func.posts import get_post_security_hash

revision = "57aafb3bf9f0"
down_revision = "adcd63ff76a2"
branch_labels = None
depends_on = None


def upgrade():
    conn = op.get_bind()

    op.add_column("post", sa.Column("camera", sa.Text(), nullable=True))

    op.add_column(
        "post", sa.Column("date_taken", sa.DateTime(), nullable=True)
    )

    posts = sa.Table(
        "post",
        sa.MetaData(),
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("camera", sa.Text, nullable=True),
        sa.Column("date_taken", sa.DateTime, nullable=True),
        sa.Column("mime-type", sa.Unicode(32), nullable=False),
        sa.Column("type", sa.Unicode(32), nullable=False),
    )

    for post in conn.execute(posts.select().where(posts.c.type != "flash")):
        ext = get_extension(post["mime-type"])
        filename = f"{post.id}_{get_post_security_hash(post.id)}.{ext}"

        content = open(_get_full_path("posts/" + filename), "rb").read()

        if post.type == "image":
            media = Image(content)
        else:
            media = Video(content)

        conn.execute(
            posts.update()
            .where(posts.c.id == post.id)
            .values(
                camera=media.camera,
                date_taken=media.date_taken,
            )
        )


def downgrade():
    op.drop_column("post", "camera")
    op.drop_column("post", "date_taken")
