"""
Add hashes to post file names

Revision ID: 02ef5f73f4ab
Created at: 2017-08-24 13:30:46.766928
"""

import os
import re

from szurubooru.func import files, posts

revision = "02ef5f73f4ab"
down_revision = "5f00af3004a4"
branch_labels = None
depends_on = None


def upgrade():
    for name in ["posts", "posts/custom-thumbnails", "generated-thumbnails"]:
        for entry in list(files.scan(name)):
            match = re.match(r"^(?P<name>\d+)\.(?P<ext>\w+)$", entry.name)
            if match:
                post_id = int(match.group("name"))
                security_hash = posts.get_post_security_hash(post_id)
                ext = match.group("ext")
                new_name = "%s_%s.%s" % (post_id, security_hash, ext)
                new_path = os.path.join(os.path.dirname(entry.path), new_name)
                os.rename(entry.path, new_path)


def downgrade():
    for name in ["posts", "posts/custom-thumbnails", "generated-thumbnails"]:
        for entry in list(files.scan(name)):
            match = re.match(
                r"^(?P<name>\d+)_(?P<hash>[0-9A-Fa-f]+)\.(?P<ext>\w+)$",
                entry.name,
            )
            if match:
                post_id = int(match.group("name"))
                security_hash = match.group("hash")  # noqa: F841
                ext = match.group("ext")
                new_name = "%s.%s" % (post_id, ext)
                new_path = os.path.join(os.path.dirname(entry.path), new_name)
                os.rename(entry.path, new_path)
