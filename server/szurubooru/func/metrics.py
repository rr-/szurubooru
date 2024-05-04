import sqlalchemy as sa
from typing import Any, Optional, List, Dict, Callable
from szurubooru import db, model, errors, rest
from szurubooru.func import serialization, tags, util, versions


class MetricDoesNotExistsError(errors.ValidationError):
    pass


class MetricAlreadyExistsError(errors.ValidationError):
    pass


class InvalidMetricError(errors.ValidationError):
    pass


class PostMissingTagError(errors.ValidationError):
    pass


class MetricValueOutOfRangeError(errors.ValidationError):
    pass


class MetricSerializer(serialization.BaseSerializer):
    def __init__(self, metric: model.Metric):
        self.metric = metric

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        return {
            "version": lambda: self.metric.version,
            "min": lambda: self.metric.min,
            "max": lambda: self.metric.max,
            "exact_count": lambda: self.metric.post_metric_count,
            "range_count": lambda: self.metric.post_metric_range_count,
            "tag": lambda: tags.serialize_tag(self.metric.tag, [
                "names", "category", "description", "usages"])
        }


class PostMetricSerializer(serialization.BaseSerializer):
    def __init__(self, post_metric: model.PostMetric):
        self.post_metric = post_metric

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        return {
            "tag_name": lambda: self.post_metric.metric.tag_name,
            "post_id": lambda: self.post_metric.post_id,
            "value": lambda: self.post_metric.value,
        }


class PostMetricRangeSerializer(serialization.BaseSerializer):
    def __init__(self, post_metric_range: model.PostMetricRange):
        self.post_metric_range = post_metric_range

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        return {
            "tag_name": lambda: self.post_metric_range.metric.tag_name,
            "post_id": lambda: self.post_metric_range.post_id,
            "low": lambda: self.post_metric_range.low,
            "high": lambda: self.post_metric_range.high,
        }


def serialize_metric(
        metric: model.Metric,
        options: List[str] = []) -> Optional[rest.Response]:
    if not metric:
        return None
    return MetricSerializer(metric).serialize(options)


def serialize_post_metric(
        post_metric: model.PostMetric,
        options: List[str] = []) -> Optional[rest.Response]:
    if not post_metric:
        return None
    return PostMetricSerializer(post_metric).serialize(options)


def serialize_post_metric_range(
        post_metric_range: model.PostMetricRange,
        options: List[str] = []) -> Optional[rest.Response]:
    if not post_metric_range:
        return None
    return PostMetricRangeSerializer(post_metric_range).serialize(options)


def try_get_metric_by_tag_name(tag_name: str) -> Optional[model.Metric]:
    return (
        db.session
        .query(model.Metric)
        .filter(sa.func.lower(model.Metric.tag_name) == tag_name.lower())
        .one_or_none())


def get_metric_by_tag_name(tag_name: str) -> model.Metric:
    metric = try_get_metric_by_tag_name(tag_name)
    if not metric:
        raise MetricDoesNotExistsError("Metric %r not found." % tag_name)
    return metric


def get_all_metrics() -> List[model.Metric]:
    return db.session.query(model.Metric).all()


def get_all_metric_tag_names() -> List[str]:
    return [
        tag_name.name for tag_name in util.flatten_list(
            [metric.tag.names for metric in get_all_metrics()]
        )
    ]


def try_get_post_metric(
        post: model.Post,
        metric: model.Metric) -> Optional[model.PostMetric]:
    return (
        db.session
        .query(model.PostMetric)
        .filter(model.PostMetric.metric == metric)
        .filter(model.PostMetric.post == post)
        .one_or_none())


def try_get_post_metric_range(
        post: model.Post,
        metric: model.Metric) -> Optional[model.PostMetricRange]:
    return (
        db.session
        .query(model.PostMetricRange)
        .filter(model.PostMetricRange.metric == metric)
        .filter(model.PostMetricRange.post == post)
        .one_or_none())


def create_metric(
        tag: model.Tag,
        min: float,
        max: float) -> model.Metric:
    assert tag
    if tag.metric:
        raise MetricAlreadyExistsError("Tag already has a metric.")
    if min >= max:
        raise InvalidMetricError("Metric min(%r) >= max(%r)" % (min, max))
    metric = model.Metric(tag=tag, min=min, max=max)
    db.session.add(metric)
    return metric


def update_or_create_metric(
        tag: model.Tag,
        metric_data: Any) -> Optional[model.Metric]:
    assert tag
    for field in ("min", "max"):
        if field not in metric_data:
            raise InvalidMetricError("Metric is missing %r field." % field)

    min, max = metric_data["min"], metric_data["max"]
    if min >= max:
        raise InvalidMetricError("Metric min(%r) >= max(%r)" % (min, max))
    if tag.metric:
        tag.metric.min = min
        tag.metric.max = max
        versions.bump_version(tag.metric)
        return None
    else:
        return create_metric(tag=tag, min=min, max=max)


def update_or_create_post_metric(
        post: model.Post,
        metric: model.Metric,
        value: float) -> model.PostMetric:
    assert post
    assert metric
    if metric.tag not in post.tags:
        raise PostMissingTagError(
            "Post doesn\"t have this tag.")
    if value < metric.min or value > metric.max:
        raise MetricValueOutOfRangeError(
            "Metric value %r out of range." % value)
    post_metric = try_get_post_metric(post, metric)
    if not post_metric:
        post_metric = model.PostMetric(post=post, metric=metric, value=value)
        db.session.add(post_metric)
    else:
        post_metric.value = value
        versions.bump_version(post_metric)
    return post_metric


def update_or_create_post_metrics(post: model.Post, metrics_data: Any) -> None:
    """
    Overwrites any existing post metrics, deletes other existing post metrics.
    """
    assert post
    post.metrics = []
    for metric_data in metrics_data:
        for field in ("tag_name", "value"):
            if field not in metric_data:
                raise InvalidMetricError("Metric is missing %r field." % field)
        value = float(metric_data["value"])
        tag_name = metric_data["tag_name"]
        tag = tags.get_tag_by_name(tag_name)
        if not tag.metric:
            raise MetricDoesNotExistsError(
                "Tag %r has no metric." % tag_name)
        post_metric = update_or_create_post_metric(post, tag.metric, value)
        post.metrics.append(post_metric)


def update_or_create_post_metric_range(
        post: model.Post,
        metric: model.Metric,
        low: float,
        high: float) -> model.PostMetricRange:
    assert post
    assert metric
    if metric.tag not in post.tags:
        raise PostMissingTagError(
            "Post doesn\"t have this tag.")
    for value in (low, high):
        if value < metric.min or value > metric.max:
            raise MetricValueOutOfRangeError(
                "Metric value %r out of range." % value)
    if low >= high:
        raise InvalidMetricError(
            "Metric range low(%r) >= high(%r)" % (low, high))
    post_metric_range = try_get_post_metric_range(post, metric)
    if not post_metric_range:
        post_metric_range = model.PostMetricRange(
            post=post, metric=metric, low=low, high=high)
        db.session.add(post_metric_range)
    else:
        post_metric_range.low = low
        post_metric_range.high = high
        versions.bump_version(post_metric_range)
    return post_metric_range


def update_or_create_post_metric_ranges(
        post: model.Post,
        metric_ranges_data: Any) -> None:
    """
    Overwrites any existing post metrics, deletes other existing post metrics.
    """
    assert post
    post.metric_ranges = []
    for metric_data in metric_ranges_data:
        for field in ("tag_name", "low", "high"):
            if field not in metric_data:
                raise InvalidMetricError(
                    "Metric range is missing %r field." % field)
        low = float(metric_data["low"])
        high = float(metric_data["high"])
        tag_name = metric_data["tag_name"]
        tag = tags.get_tag_by_name(tag_name)
        if not tag.metric:
            raise MetricDoesNotExistsError(
                "Tag %r has no metric." % tag_name)
        post_metric_range = update_or_create_post_metric_range(
            post, tag.metric, low, high)
        post.metric_ranges.append(post_metric_range)


def delete_metric(metric: model.Metric) -> None:
    assert metric
    db.session.delete(metric)
