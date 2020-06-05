"""
Add order to tag names

Revision ID: 9837fc981ec7
Created at: 2016-08-28 19:03:59.831527
"""

import sqlalchemy as sa
import sqlalchemy.ext.declarative
from alembic import op

revision = "9837fc981ec7"
down_revision = "4a020f1d271a"
branch_labels = None
depends_on = None


Base = sa.ext.declarative.declarative_base()


class TagName(Base):
    __tablename__ = "tag_name"
    __table_args__ = {"extend_existing": True}

    tag_name_id = sa.Column("tag_name_id", sa.Integer, primary_key=True)
    ord = sa.Column("ord", sa.Integer, nullable=False, index=True)


def upgrade():
    op.add_column("tag_name", sa.Column("ord", sa.Integer(), nullable=True))
    op.execute(TagName.__table__.update().values(ord=TagName.tag_name_id))
    op.alter_column("tag_name", "ord", nullable=False)
    op.create_index(op.f("ix_tag_name_ord"), "tag_name", ["ord"], unique=False)


def downgrade():
    op.drop_index(op.f("ix_tag_name_ord"), table_name="tag_name")
    op.drop_column("tag_name", "ord")
