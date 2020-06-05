import sqlalchemy as sa
from sqlalchemy.ext.associationproxy import association_proxy
from sqlalchemy.ext.orderinglist import ordering_list

from szurubooru.model.base import Base


class PoolName(Base):
    __tablename__ = "pool_name"

    pool_name_id = sa.Column("pool_name_id", sa.Integer, primary_key=True)
    pool_id = sa.Column(
        "pool_id",
        sa.Integer,
        sa.ForeignKey("pool.id"),
        nullable=False,
        index=True,
    )
    name = sa.Column("name", sa.Unicode(128), nullable=False, unique=True)
    order = sa.Column("ord", sa.Integer, nullable=False, index=True)

    def __init__(self, name: str, order: int) -> None:
        self.name = name
        self.order = order


class PoolPost(Base):
    __tablename__ = "pool_post"

    pool_id = sa.Column(
        "pool_id",
        sa.Integer,
        sa.ForeignKey("pool.id"),
        nullable=False,
        primary_key=True,
        index=True,
    )
    post_id = sa.Column(
        "post_id",
        sa.Integer,
        sa.ForeignKey("post.id"),
        nullable=False,
        primary_key=True,
        index=True,
    )
    order = sa.Column("ord", sa.Integer, nullable=False, index=True)

    pool = sa.orm.relationship("Pool", back_populates="_posts")
    post = sa.orm.relationship("Post", back_populates="_pools")

    def __init__(self, post) -> None:
        self.post_id = post.post_id


class Pool(Base):
    __tablename__ = "pool"

    pool_id = sa.Column("id", sa.Integer, primary_key=True)
    category_id = sa.Column(
        "category_id",
        sa.Integer,
        sa.ForeignKey("pool_category.id"),
        nullable=False,
        index=True,
    )
    version = sa.Column("version", sa.Integer, default=1, nullable=False)
    creation_time = sa.Column("creation_time", sa.DateTime, nullable=False)
    last_edit_time = sa.Column("last_edit_time", sa.DateTime)
    description = sa.Column("description", sa.UnicodeText, default=None)

    category = sa.orm.relationship("PoolCategory", lazy="joined")
    names = sa.orm.relationship(
        "PoolName",
        cascade="all,delete-orphan",
        lazy="joined",
        order_by="PoolName.order",
    )
    _posts = sa.orm.relationship(
        "PoolPost",
        cascade="all,delete-orphan",
        lazy="joined",
        back_populates="pool",
        order_by="PoolPost.order",
        collection_class=ordering_list("order"),
    )
    posts = association_proxy("_posts", "post")

    post_count = sa.orm.column_property(
        (
            sa.sql.expression.select(
                [sa.sql.expression.func.count(PoolPost.post_id)]
            )
            .where(PoolPost.pool_id == pool_id)
            .as_scalar()
        ),
        deferred=True,
    )

    first_name = sa.orm.column_property(
        (
            sa.sql.expression.select([PoolName.name])
            .where(PoolName.pool_id == pool_id)
            .order_by(PoolName.order)
            .limit(1)
            .as_scalar()
        ),
        deferred=True,
    )

    __mapper_args__ = {
        "version_id_col": version,
        "version_id_generator": False,
    }
