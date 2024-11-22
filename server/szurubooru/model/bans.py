from typing import List

import sqlalchemy as sa

from szurubooru.model.base import Base

class PostBan(Base):
    __tablename__ = "post_ban"

    ban_id = sa.Column("id", sa.Integer, primary_key=True)
    checksum = sa.Column("checksum", sa.Unicode(64), nullable=False)
    time = sa.Column("time", sa.DateTime, nullable=False)
