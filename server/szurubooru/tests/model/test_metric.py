from szurubooru import db, model

import pytest

@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {"secret": "secret", "data_dir": "", "delete_source_files": False}
    )

def test_saving_metric(post_factory, tag_factory):
    tag = tag_factory()
    post = post_factory(tags=[tag])
    metric = model.Metric(tag=tag, min=1., max=10.)
    post_metric = model.PostMetric(metric=metric, post=post, value=5.5)
    post_metric_range = model.PostMetricRange(metric=metric, post=post,
                                              low=2., high=8.)
    db.session.add_all([post, tag, metric, post_metric, post_metric_range])
    db.session.commit()

    assert metric.tag_id is not None
    assert post_metric.tag_id is not None
    assert post_metric.post_id is not None
    assert post_metric_range.tag_id is not None
    assert post_metric_range.post_id is not None
    assert tag.metric.tag_id == tag.tag_id
    assert tag.metric.min == 1.
    assert tag.metric.max == 10.

    metric = (
        db.session
        .query(model.Metric)
        .filter(model.Metric.tag_id == tag.tag_id)
        .one())
    assert metric.min == 1.
    assert metric.max == 10.

    post_metric = (
        db.session
        .query(model.PostMetric)
        .filter(model.PostMetric.tag_id == tag.tag_id and
                model.PostMetric.post_id == post.post_id)
        .one())
    assert post_metric.value == 5.5

    post_metric_range = (
        db.session
        .query(model.PostMetricRange)
        .filter(model.PostMetricRange.tag_id == tag.tag_id and
                model.PostMetricRange.post_id == post.post_id)
        .one())
    assert post_metric_range.low == 2.
    assert post_metric_range.high == 8.

    tag = (
        db.session
        .query(model.Tag)
        .filter(model.Tag.tag_id == metric.tag_id)
        .one())
    assert tag.metric == metric


def test_cascade_delete_metric(post_factory, tag_factory):
    tag = tag_factory()
    post1 = post_factory(tags=[tag])
    post2 = post_factory(tags=[tag])
    metric = model.Metric(tag=tag, min=1., max=10.)
    post_metric1 = model.PostMetric(metric=metric, post=post1, value=2.3)
    post_metric2 = model.PostMetric(metric=metric, post=post2, value=4.5)
    post_metric_range = model.PostMetricRange(
        metric=metric, post=post2, low=2, high=8)
    db.session.add_all([post1, post2, tag, metric, post_metric1, post_metric2,
                        post_metric_range])
    db.session.flush()

    assert not db.session.dirty
    assert db.session.query(model.Post).count() == 2
    assert db.session.query(model.Tag).count() == 1
    assert db.session.query(model.Metric).count() == 1
    assert db.session.query(model.PostMetric).count() == 2
    assert db.session.query(model.PostMetricRange).count() == 1

    db.session.delete(metric)
    db.session.commit()

    assert not db.session.dirty
    assert db.session.query(model.Post).count() == 2
    assert db.session.query(model.Tag).count() == 1
    assert db.session.query(model.Metric).count() == 0
    assert db.session.query(model.PostMetric).count() == 0
    assert db.session.query(model.PostMetricRange).count() == 0


def test_cascade_delete_tag(post_factory, tag_factory):
    tag1 = tag_factory()
    tag2 = tag_factory()
    post = post_factory(tags=[tag1, tag2])
    metric1 = model.Metric(tag=tag1, min=1., max=10.)
    metric2 = model.Metric(tag=tag2, min=2., max=20.)
    post_metric1 = model.PostMetric(metric=metric1, post=post, value=2.3)
    post_metric2 = model.PostMetric(metric=metric2, post=post, value=4.5)
    post_metric_range1 = model.PostMetricRange(
        metric=metric1, post=post, low=2, high=8)
    post_metric_range2 = model.PostMetricRange(
        metric=metric2, post=post, low=2, high=8)
    db.session.add_all([post, tag1, tag2, metric1, metric2, post_metric1,
                        post_metric2, post_metric_range1, post_metric_range2])
    db.session.commit()

    assert not db.session.dirty
    assert db.session.query(model.Post).count() == 1
    assert db.session.query(model.Tag).count() == 2
    assert db.session.query(model.Metric).count() == 2
    assert db.session.query(model.PostMetric).count() == 2
    assert db.session.query(model.PostMetricRange).count() == 2

    db.session.delete(tag2)
    db.session.commit()

    assert not db.session.dirty
    assert db.session.query(model.Post).count() == 1
    assert db.session.query(model.Tag).count() == 1
    assert db.session.query(model.Metric).count() == 1
    assert db.session.query(model.PostMetric).count() == 1
    assert db.session.query(model.PostMetricRange).count() == 1


def test_cascade_delete_post(post_factory, tag_factory):
    tag = tag_factory()
    post1 = post_factory(tags=[tag])
    post2 = post_factory(tags=[tag])
    metric = model.Metric(tag=tag, min=1., max=10.)
    post_metric1 = model.PostMetric(metric=metric, post=post1, value=2.3)
    post_metric2 = model.PostMetric(metric=metric, post=post2, value=4.5)
    post_metric_range1 = model.PostMetricRange(
        metric=metric, post=post1, low=2, high=8)
    post_metric_range2 = model.PostMetricRange(
        metric=metric, post=post2, low=2, high=8)
    db.session.add_all([post1, post2, tag, metric, post_metric1, post_metric2,
                        post_metric_range1, post_metric_range2])
    db.session.commit()

    assert not db.session.dirty
    assert db.session.query(model.Post).count() == 2
    assert db.session.query(model.Tag).count() == 1
    assert db.session.query(model.Metric).count() == 1
    assert db.session.query(model.PostMetric).count() == 2
    assert db.session.query(model.PostMetricRange).count() == 2

    db.session.delete(post2)
    db.session.commit()

    assert not db.session.dirty
    assert db.session.query(model.Post).count() == 1
    assert db.session.query(model.Tag).count() == 1
    assert db.session.query(model.Metric).count() == 1
    assert db.session.query(model.PostMetric).count() == 1
    assert db.session.query(model.PostMetricRange).count() == 1


def test_delete_post_metric_no_cascade(
        post_factory, tag_factory, metric_factory,
        post_metric_factory, post_metric_range_factory):
    tag = tag_factory()
    post = post_factory(tags=[tag])
    metric = metric_factory(tag=tag)
    post_metric = post_metric_factory(post=post, metric=metric)
    post_metric_range = post_metric_range_factory(post=post, metric=metric)
    db.session.add(metric)
    db.session.commit()
    assert len(metric.post_metrics) == 1

    db.session.delete(post_metric)
    db.session.delete(post_metric_range)
    db.session.commit()
    assert len(metric.post_metrics) == 0
    assert len(metric.post_metric_ranges) == 0


def test_tag_without_metric(tag_factory):
    tag = tag_factory(names=['mytag'])
    assert tag.metric is None
    db.session.add(tag)
    db.session.commit()
    tag = (
        db.session
        .query(model.Tag)
        .join(model.TagName)
        .filter(model.TagName.name == 'mytag')
        .one())
    assert tag.metric is None


def test_metric_counts(post_factory, metric_factory):
    metric = metric_factory()
    post1 = post_factory(tags=[metric.tag])
    post2 = post_factory(tags=[metric.tag])
    post_metric1 = model.PostMetric(post=post1, metric=metric, value=1.2)
    post_metric2 = model.PostMetric(post=post2, metric=metric, value=3.4)
    post_metric_range = model.PostMetricRange(post=post1, metric=metric, low=5.6, high=7.8)
    db.session.add_all([metric, post_metric1, post_metric2, post_metric_range])
    db.session.flush()
    assert metric.post_metric_count == 2
    assert metric.post_metric_range_count == 1


def test_cascade_on_remove_tag_from_post(
        post_factory, tag_factory, metric_factory,
        post_metric_factory, post_metric_range_factory):
    tag = tag_factory()
    post = post_factory(tags=[tag])
    metric = metric_factory(tag=tag)
    post_metric = post_metric_factory(post=post, metric=metric)
    post_metric_range = post_metric_range_factory(post=post, metric=metric)
    db.session.add_all([post, tag, metric, post_metric, post_metric_range])
    db.session.commit()

    assert not db.session.dirty
    assert db.session.query(model.Post).count() == 1
    assert db.session.query(model.Tag).count() == 1
    assert db.session.query(model.PostTag).count() == 1
    assert db.session.query(model.Metric).count() == 1
    assert db.session.query(model.PostMetric).count() == 1
    assert db.session.query(model.PostMetricRange).count() == 1

    post.tags.clear()
    db.session.commit()

    assert not db.session.dirty
    assert db.session.query(model.Post).count() == 1
    assert db.session.query(model.Tag).count() == 1
    assert db.session.query(model.PostTag).count() == 0
    assert db.session.query(model.Metric).count() == 1
    assert db.session.query(model.PostMetric).count() == 0
    assert db.session.query(model.PostMetricRange).count() == 0
