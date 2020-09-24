from typing import Optional

import sqlalchemy as sa

from szurubooru.model.base import Base
from szurubooru.model.tag import Tag


class TagCategory(Base):
    __tablename__ = "tag_category"

    tag_category_id = sa.Column("id", sa.Integer, primary_key=True)
    version = sa.Column("version", sa.Integer, default=1, nullable=False)
    name = sa.Column("name", sa.Unicode(32), nullable=False)
    color = sa.Column(
        "color", sa.Unicode(32), nullable=False, default="#000000"
    )
    default = sa.Column("default", sa.Boolean, nullable=False, default=False)
    order = sa.Column("order", sa.Integer, nullable=False, default=1)

    def __init__(self, name: Optional[str] = None) -> None:
        self.name = name

    tag_count = sa.orm.column_property(
        sa.sql.expression.select([sa.sql.expression.func.count("Tag.tag_id")])
        .where(Tag.category_id == tag_category_id)
        .correlate_except(sa.table("Tag"))
    )

    __mapper_args__ = {
        "version_id_col": version,
        "version_id_generator": False,
    }
