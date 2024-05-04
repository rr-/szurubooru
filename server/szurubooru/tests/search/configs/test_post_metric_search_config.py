import pytest
from szurubooru import db, model, errors, search


@pytest.fixture
def executor():
    return search.Executor(search.configs.PostMetricSearchConfig())


@pytest.fixture
def verify_unpaged(executor):
    def verify(input, expected_values):
        actual_count, actual_post_metrics = executor.execute(
            input, offset=0, limit=100)
        actual_values = ['%s:%r' % (u.metric.tag_name, u.value)
                         for u in actual_post_metrics]
        assert actual_count == len(expected_values)
        assert actual_values == expected_values
    return verify


def test_refresh_metrics(tag_factory, metric_factory):
    tag1 = tag_factory(names=['tag1'])
    tag2 = tag_factory(names=['tag2'])
    metric1 = metric_factory(tag1)
    metric2 = metric_factory(tag2)
    db.session.add_all([tag1, tag2, metric1, metric2])
    db.session.flush()

    config = search.configs.PostMetricSearchConfig()
    config.refresh_metrics()

    assert config.all_metric_names == ['tag1', 'tag2']


@pytest.mark.parametrize('input,expected_tag_names', [
    ('', ['t1:10', 't2:20.5', 't1:30', 't2:40']),
    ('*', ['t1:10', 't2:20.5', 't1:30', 't2:40']),
    ('t1', ['t1:10', 't1:30']),
    ('t2', ['t2:20.5', 't2:40']),
    ('t*', ['t1:10', 't2:20.5', 't1:30', 't2:40']),
    ('t1,t2', ['t1:10', 't2:20.5', 't1:30', 't2:40']),
    ('T1,T2', ['t1:10', 't2:20.5', 't1:30', 't2:40']),
])
def test_filter_anonymous(
        verify_unpaged, input, expected_tag_names,
        post_factory, tag_factory, metric_factory, post_metric_factory):
    tag1 = tag_factory(names=['t1'])
    tag2 = tag_factory(names=['t2'])
    post1 = post_factory(tags=[tag1, tag2])
    post2 = post_factory(tags=[tag1, tag2])
    metric1 = metric_factory(tag1)
    metric2 = metric_factory(tag2)
    t1_10 = post_metric_factory(post=post1, metric=metric1, value=10)
    t1_30 = post_metric_factory(post=post2, metric=metric1, value=30)
    t2_20 = post_metric_factory(post=post1, metric=metric2, value=20.5)
    t2_40 = post_metric_factory(post=post2, metric=metric2, value=40)
    db.session.add_all([tag1, tag2, metric1, metric2,
                        t1_10, t1_30, t2_20, t2_40])
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize('input,expected_tag_names', [
    ('t:13', []),
    ('t:10', ['t:10']),
    ('t:20.5', ['t:20.5']),
    ('t:18.6..', ['t:20.5', 't:30', 't:40']),
    ('t-min:18.6', ['t:20.5', 't:30', 't:40']),
    ('t:..21.4', ['t:10', 't:20.5']),
    ('t-max:21.4', ['t:10', 't:20.5']),
    ('t:17..33', ['t:20.5', 't:30']),
])
def test_filter_by_value(
        verify_unpaged, input, expected_tag_names,
        post_factory, tag_factory, metric_factory, post_metric_factory):
    tag = tag_factory(names=['t'])
    post1 = post_factory(tags=[tag])
    post2 = post_factory(tags=[tag])
    post3 = post_factory(tags=[tag])
    post4 = post_factory(tags=[tag])
    metric = metric_factory(tag)
    t1 = post_metric_factory(post=post1, metric=metric, value=10)
    t2 = post_metric_factory(post=post2, metric=metric, value=30)
    t3 = post_metric_factory(post=post3, metric=metric, value=20.5)
    t4 = post_metric_factory(post=post4, metric=metric, value=40)
    db.session.add_all([tag, metric, t1, t2, t3, t4])
    db.session.flush()
    verify_unpaged(input, expected_tag_names)
