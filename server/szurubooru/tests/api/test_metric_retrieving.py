from szurubooru import api, db, model

import pytest


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "metrics:list": model.User.RANK_REGULAR,
            },
        }
    )

@pytest.mark.parametrize('query,expected_value', [
    ('', 5),
    ('mytag:0..', 5),
    ('mytag:..10', 5),
    ('mytag:0..10', 5),
    ('mytag:2..8', 5),
    ('mytag:0..8', 4),
    ('mytag:0..6', 4),
    ('mytag:0..5.5', 4),
    ('mytag:0..4', 1),
    ('mytag:1..4', 1),
    ('mytag:2..3', None),
])
def test_median(
        query,
        expected_value,
        tag_factory,
        post_factory,
        metric_factory,
        post_metric_factory,
        context_factory,
        user_factory):
    tag = tag_factory(names=['mytag'])
    post1 = post_factory(tags=[tag])
    post4 = post_factory(tags=[tag])
    post5 = post_factory(tags=[tag])
    post6 = post_factory(tags=[tag])
    post10 = post_factory(tags=[tag])
    metric = metric_factory(tag=tag, min=0, max=10)
    pm1 = post_metric_factory(metric=metric, post=post1, value=1)
    pm4 = post_metric_factory(metric=metric, post=post4, value=4)
    pm5 = post_metric_factory(metric=metric, post=post5, value=5)
    pm6 = post_metric_factory(metric=metric, post=post6, value=6)
    pm10 = post_metric_factory(metric=metric, post=post10, value=10)
    db.session.add_all([tag, metric, pm1, pm4, pm5, pm6, pm10,
                        post1, post4, post5, post6, post10])
    db.session.flush()
    response = api.metric_api.get_post_metrics_median(
        context_factory(
            params={'query': query},
            user=user_factory(rank=model.User.RANK_REGULAR)),
        {'tag_name': 'mytag'})
    if not expected_value:
        assert response['total'] == 0
        assert len(response['results']) == 0
    else:
        assert response['total'] == 1
        assert response['results'][0]['value'] == expected_value
