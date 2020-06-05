from typing import Optional

import sqlalchemy as sa

from szurubooru.model.base import Base
from szurubooru.model.pool import Pool


class PoolCategory(Base):
    __tablename__ = "pool_category"

    pool_category_id = sa.Column("id", sa.Integer, primary_key=True)
    version = sa.Column("version", sa.Integer, default=1, nullable=False)
    name = sa.Column("name", sa.Unicode(32), nullable=False)
    color = sa.Column(
        "color", sa.Unicode(32), nullable=False, default="#000000"
    )
    default = sa.Column("default", sa.Boolean, nullable=False, default=False)

    def __init__(self, name: Optional[str] = None) -> None:
        self.name = name

    pool_count = sa.orm.column_property(
        sa.sql.expression.select(
            [sa.sql.expression.func.count("Pool.pool_id")]
        )
        .where(Pool.category_id == pool_category_id)
        .correlate_except(sa.table("Pool"))
    )

    __mapper_args__ = {
        "version_id_col": version,
        "version_id_generator": False,
    }
