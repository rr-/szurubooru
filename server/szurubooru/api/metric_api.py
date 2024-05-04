from math import ceil
from typing import Optional, List, Dict
from szurubooru import db, model, search, rest
from szurubooru.func import (
    auth, metrics, snapshots, serialization, tags, versions
)


_search_executor_config = search.configs.PostMetricSearchConfig()
_search_executor = search.Executor(_search_executor_config)


def _serialize_metric(
        ctx: rest.Context, metric: model.Metric) -> rest.Response:
    return metrics.serialize_metric(
        metric, options=serialization.get_serialization_options(ctx)
    )


def _serialize_post_metric(
        ctx: rest.Context, post_metric: model.PostMetric) -> rest.Response:
    return metrics.serialize_post_metric(
        post_metric, options=serialization.get_serialization_options(ctx)
    )


def _get_metric(params: Dict[str, str]) -> model.Metric:
    return metrics.get_metric_by_tag_name(params["tag_name"])


@rest.routes.get("/metrics/?")
def get_metrics(
        ctx: rest.Context, params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, "metrics:list")
    all_metrics = metrics.get_all_metrics()
    return {
        "results": [_serialize_metric(ctx, metric) for metric in all_metrics]
    }


@rest.routes.post("/metrics/?")
def create_metric(
        ctx: rest.Context, params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, "metrics:create")
    tag_name = ctx.get_param_as_string("tag_name")
    tag = tags.get_tag_by_name(tag_name)
    min = ctx.get_param_as_float("min")
    max = ctx.get_param_as_float("max")

    metric = metrics.create_metric(tag, min, max)
    ctx.session.flush()
    # snapshots.create(metric, ctx.user)
    ctx.session.commit()
    return _serialize_metric(ctx, metric)


@rest.routes.delete("/metric/(?P<tag_name>.+)")
def delete_metric(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    metric = _get_metric(params)
    versions.verify_version(metric, ctx)
    auth.verify_privilege(ctx.user, "metrics:delete")
    # snapshots.delete(metric, ctx.user)
    metrics.delete_metric(metric)
    ctx.session.commit()
    return {}


@rest.routes.get("/post-metrics/?")
def get_post_metrics(
        ctx: rest.Context, params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, "metrics:list")
    return _search_executor.execute_and_serialize(
        ctx, lambda post_metric: _serialize_post_metric(ctx, post_metric))


@rest.routes.get("/post-metrics/median/(?P<tag_name>.+)")
def get_post_metrics_median(
        ctx: rest.Context, params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, "metrics:list")
    metric = _get_metric(params)
    tag_name = params["tag_name"]
    query_text = ctx.get_param_as_string(
        "query",
        default="%s:%f..%f" % (tag_name, metric.min, metric.max))
    total_count = _search_executor.count(query_text)
    offset = ceil(total_count/2) - 1
    _, results = _search_executor.execute(query_text, offset, 1)
    return {
        "query": query_text,
        "offset": offset,
        "limit": 1,
        "total": len(results),
        "results": list([_serialize_post_metric(ctx, pm) for pm in results])
    }
