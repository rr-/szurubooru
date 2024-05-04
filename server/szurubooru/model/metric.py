import sqlalchemy as sa
from szurubooru.model.base import Base
from szurubooru.model.post import PostTag
from szurubooru.model.tag import TagName


class PostMetric(Base):
    __tablename__ = 'post_metric'

    post_id = sa.Column(
        'post_id',
        sa.Integer,
        sa.ForeignKey('post.id'),
        primary_key=True,
        nullable=False,
        index=True)
    tag_id = sa.Column(
        'tag_id',
        sa.Integer,
        sa.ForeignKey('metric.tag_id'),
        primary_key=True,
        nullable=False,
        index=True)
    version = sa.Column('version', sa.Integer, default=1, nullable=False)
    value = sa.Column('value', sa.Float, nullable=False, index=True)

    post = sa.orm.relationship('Post')
    metric = sa.orm.relationship('Metric', back_populates='post_metrics')

    __table_args__ = (sa.ForeignKeyConstraint(
        (post_id, tag_id),
        (PostTag.post_id, PostTag.tag_id),
        ondelete='cascade'),
    )
    __mapper_args__ = {
        'version_id_col': version,
        'version_id_generator': False,
        # when deleting tag or post, cascade will ensure this post metric is
        # also deleted, but sqlalchemy will try to delete it twice because of
        # the cascade on foreign key into PostTag. This silences the error:
        'confirm_deleted_rows': False,
    }


class PostMetricRange(Base):
    """
    Could be a metric in the process of finding its exact value, e.g. by sorting.
    It has upper and lower boundaries that will converge at the final value.
    """
    __tablename__ = 'post_metric_range'

    post_id = sa.Column(
        'post_id',
        sa.Integer,
        sa.ForeignKey('post.id'),
        primary_key=True,
        nullable=False,
        index=True)
    tag_id = sa.Column(
        'tag_id',
        sa.Integer,
        sa.ForeignKey('metric.tag_id'),
        primary_key=True,
        nullable=False,
        index=True)
    version = sa.Column('version', sa.Integer, default=1, nullable=False)
    low = sa.Column('low', sa.Float, nullable=False)
    high = sa.Column('high', sa.Float, nullable=False)

    post = sa.orm.relationship('Post')
    metric = sa.orm.relationship('Metric', back_populates='post_metric_ranges')

    __table_args__ = (sa.ForeignKeyConstraint(
        (post_id, tag_id),
        (PostTag.post_id, PostTag.tag_id),
        ondelete='cascade'),
    )
    __mapper_args__ = {
        'version_id_col': version,
        'version_id_generator': False,
        # when deleting tag or post, cascade will ensure this post metric is
        # also deleted, but sqlalchemy will try to delete it twice because of
        # the cascade on foreign key into PostTag. This silences the error:
        'confirm_deleted_rows': False,
    }


class Metric(Base):
    """
    Must be attached to a tag, tag_id is primary key.
    """
    __tablename__ = 'metric'

    tag_id = sa.Column(
        'tag_id',
        sa.Integer,
        sa.ForeignKey('tag.id'),
        primary_key=True,
        nullable=False,
        index=True)
    version = sa.Column('version', sa.Integer, default=1, nullable=False)
    min = sa.Column('min', sa.Float, nullable=False)
    max = sa.Column('max', sa.Float, nullable=False)

    tag = sa.orm.relationship('Tag')
    post_metrics = sa.orm.relationship(
        'PostMetric', back_populates='metric', cascade='all, delete-orphan')
    post_metric_ranges = sa.orm.relationship(
        'PostMetricRange', back_populates='metric', cascade='all, delete-orphan')

    tag_name = sa.orm.column_property(
        (
            sa.sql.expression.select([TagName.name])
                .where(TagName.tag_id == tag_id)
                .order_by(TagName.order)
                .limit(1)
                .as_scalar()
        ))

    post_metric_count = sa.orm.column_property(
        (
            sa.sql.expression.select(
                [sa.sql.expression.func.count(PostMetric.post_id)])
                .where(PostMetric.tag_id == tag_id)
                .correlate_except(PostMetric)
        ),
        deferred=True)

    post_metric_range_count = sa.orm.column_property(
        (
            sa.sql.expression.select(
                [sa.sql.expression.func.count(PostMetricRange.post_id)])
                .where(PostMetricRange.tag_id == tag_id)
                .correlate_except(PostMetricRange)
        ),
        deferred=True)

    __mapper_args__ = {
        'version_id_col': version,
        'version_id_generator': False,
    }
