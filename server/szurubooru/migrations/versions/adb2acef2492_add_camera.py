"""
add_camera

Revision ID: adb2acef2492
Created at: 2021-12-01 13:06:14.285699
"""

import sqlalchemy as sa
from alembic import op

from szurubooru.func import files, metadata

revision = "adb2acef2492"
down_revision = "57aafb3bf9f0"
branch_labels = None
depends_on = None


def upgrade():
    conn = op.get_bind()

    op.add_column("post", sa.Column("camera", sa.Text(), nullable=True))

    posts = sa.Table(
        "post",
        sa.MetaData(),
        sa.Column("id", sa.Integer, primary_key=True),
        sa.Column("camera", sa.Text, nullable=True),
    )

    for file in list(files.scan("posts")):
        filename = file.name
        fullpath = files._get_full_path("posts/" + filename)

        post_ext = filename.split(".")[1]

        if post_ext in ["jpg", "jpeg", "png", "heif", "heic"]:
            with open(fullpath, "rb") as img:
                camera_string = metadata.resolve_image_camera(img)
        elif post_ext in ["webm", "mp4", "avif"]:
            camera_string = metadata.resolve_video_camera(
                files._get_full_path(fullpath)
            )
        else:
            continue

        post_id = int(filename.split("_")[0])

        conn.execute(
            posts.update()
            .where(posts.c.id == post_id)
            .values(camera=camera_string)
        )

    op.alter_column("post", "camera", nullable=True)


def downgrade():
    op.drop_column("post", "camera")
