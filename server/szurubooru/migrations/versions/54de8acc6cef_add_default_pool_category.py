"""
add default pool category

Revision ID: 54de8acc6cef
Created at: 2020-05-03 14:57:46.825766
"""

import sqlalchemy as sa
from alembic import op

revision = "54de8acc6cef"
down_revision = "6a2f424ec9d2"
branch_labels = None
depends_on = None


Base = sa.ext.declarative.declarative_base()


class PoolCategory(Base):
    __tablename__ = "pool_category"
    __table_args__ = {"extend_existing": True}

    pool_category_id = sa.Column("id", sa.Integer, primary_key=True)
    version = sa.Column("version", sa.Integer, nullable=False)
    name = sa.Column("name", sa.Unicode(32), nullable=False)
    color = sa.Column("color", sa.Unicode(32), nullable=False)
    default = sa.Column("default", sa.Boolean, nullable=False)

    __mapper_args__ = {
        "version_id_col": version,
        "version_id_generator": False,
    }


def upgrade():
    session = sa.orm.session.Session(bind=op.get_bind())
    if session.query(PoolCategory).count() == 0:
        category = PoolCategory()
        category.name = "default"
        category.color = "default"
        category.version = 1
        category.default = True
        session.add(category)
    session.commit()


def downgrade():
    session = sa.orm.session.Session(bind=op.get_bind())
    default_category = (
        session.query(PoolCategory)
        .filter(PoolCategory.name == "default")
        .filter(PoolCategory.color == "default")
        .filter(PoolCategory.version == 1)
        .filter(PoolCategory.default == 1)
        .one_or_none()
    )
    if default_category:
        session.delete(default_category)
    session.commit()
