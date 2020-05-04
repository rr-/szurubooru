import sqlalchemy as sa
from szurubooru.model.base import Base


class PoolName(Base):
    __tablename__ = 'pool_name'

    pool_name_id = sa.Column('pool_name_id', sa.Integer, primary_key=True)
    pool_id = sa.Column(
        'pool_id',
        sa.Integer,
        sa.ForeignKey('pool.id'),
        nullable=False,
        index=True)
    name = sa.Column('name', sa.Unicode(128), nullable=False, unique=True)
    order = sa.Column('ord', sa.Integer, nullable=False, index=True)

    def __init__(self, name: str, order: int) -> None:
        self.name = name
        self.order = order

class Pool(Base):
    __tablename__ = 'pool'

    pool_id = sa.Column('id', sa.Integer, primary_key=True)
    category_id = sa.Column(
        'category_id',
        sa.Integer,
        sa.ForeignKey('pool_category.id'),
        nullable=False,
        index=True)
    version = sa.Column('version', sa.Integer, default=1, nullable=False)
    creation_time = sa.Column('creation_time', sa.DateTime, nullable=False)
    last_edit_time = sa.Column('last_edit_time', sa.DateTime)
    description = sa.Column('description', sa.UnicodeText, default=None)

    category = sa.orm.relationship('PoolCategory', lazy='joined')
    names = sa.orm.relationship(
        'PoolName',
        cascade='all,delete-orphan',
        lazy='joined',
        order_by='PoolName.order')

    # post_count = sa.orm.column_property(
    #     sa.sql.expression.select(
    #         [sa.sql.expression.func.count(PostPool.post_id)])
    #     .where(PostPool.pool_id == pool_id)
    #     .correlate_except(PostPool))
    # TODO
    from random import randint
    post_count = sa.orm.column_property(
        sa.sql.expression.select([randint(1, 1000)])
            .limit(1)
            .as_scalar())

    first_name = sa.orm.column_property(
        (
            sa.sql.expression.select([PoolName.name])
            .where(PoolName.pool_id == pool_id)
            .order_by(PoolName.order)
            .limit(1)
            .as_scalar()
        ),
        deferred=True)


    __mapper_args__ = {
        'version_id_col': version,
        'version_id_generator': False,
    }
