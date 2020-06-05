"""
Add default tag category

Revision ID: 5f00af3004a4
Created at: 2017-02-02 20:06:13.336380
"""

import sqlalchemy as sa
import sqlalchemy.ext.declarative
import sqlalchemy.orm.session
from alembic import op

revision = "5f00af3004a4"
down_revision = "9837fc981ec7"
branch_labels = None
depends_on = None


Base = sa.ext.declarative.declarative_base()


class TagCategory(Base):
    __tablename__ = "tag_category"
    __table_args__ = {"extend_existing": True}

    tag_category_id = sa.Column("id", sa.Integer, primary_key=True)
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
    if session.query(TagCategory).count() == 0:
        category = TagCategory()
        category.name = "default"
        category.color = "default"
        category.version = 1
        category.default = True
        session.add(category)
    session.commit()


def downgrade():
    session = sa.orm.session.Session(bind=op.get_bind())
    default_category = (
        session.query(TagCategory)
        .filter(TagCategory.name == "default")
        .filter(TagCategory.color == "default")
        .filter(TagCategory.version == 1)
        .filter(TagCategory.default == 1)
        .one_or_none()
    )
    if default_category:
        session.delete(default_category)
    session.commit()
