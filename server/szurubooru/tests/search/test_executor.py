import unittest.mock

import pytest

from szurubooru import search, model
from szurubooru.func import cache


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {"posts:list:unsafe": model.User.RANK_REGULAR},
        }
    )


def test_retrieving_from_cache():
    config = unittest.mock.MagicMock()
    with unittest.mock.patch("szurubooru.func.cache.has"), unittest.mock.patch(
        "szurubooru.func.cache.get"
    ):
        cache.has.side_effect = lambda *args: True
        executor = search.Executor(config)
        executor.execute("test:whatever", 1, 10)
        assert cache.get.called


def test_putting_equivalent_queries_into_cache():
    config = search.configs.PostSearchConfig()
    with unittest.mock.patch("szurubooru.func.cache.has"), unittest.mock.patch(
        "szurubooru.func.cache.put"
    ):
        hashes = []

        def appender(key, _value):
            hashes.append(key)

        cache.has.side_effect = lambda *args: False
        cache.put.side_effect = appender
        executor = search.Executor(config)
        executor.execute("safety:safe test", 1, 10)
        executor.execute("safety:safe  test", 1, 10)
        executor.execute("safety:safe test ", 1, 10)
        executor.execute(" safety:safe test", 1, 10)
        executor.execute(" SAFETY:safe test", 1, 10)
        executor.execute("test safety:safe", 1, 10)
        assert len(hashes) == 6
        assert len(set(hashes)) == 1


def test_putting_non_equivalent_queries_into_cache():
    config = search.configs.PostSearchConfig()
    with unittest.mock.patch("szurubooru.func.cache.has"), unittest.mock.patch(
        "szurubooru.func.cache.put"
    ):
        hashes = []

        def appender(key, _value):
            hashes.append(key)

        cache.has.side_effect = lambda *args: False
        cache.put.side_effect = appender
        executor = search.Executor(config)
        args = [
            ("", 1, 10),
            ("creation-time:2016", 1, 10),
            ("creation-time:2015", 1, 10),
            ("creation-time:2016-01", 1, 10),
            ("creation-time:2016-02", 1, 10),
            ("creation-time:2016-01-01", 1, 10),
            ("creation-time:2016-01-02", 1, 10),
            ("tag-count:1,3", 1, 10),
            ("tag-count:1,2", 1, 10),
            ("tag-count:1", 1, 10),
            ("tag-count:1..3", 1, 10),
            ("tag-count:1..4", 1, 10),
            ("tag-count:2..3", 1, 10),
            ("tag-count:1..", 1, 10),
            ("tag-count:2..", 1, 10),
            ("tag-count:..3", 1, 10),
            ("tag-count:..4", 1, 10),
            ("-tag-count:1..3", 1, 10),
            ("-tag-count:1..4", 1, 10),
            ("-tag-count:2..3", 1, 10),
            ("-tag-count:1..", 1, 10),
            ("-tag-count:2..", 1, 10),
            ("-tag-count:..3", 1, 10),
            ("-tag-count:..4", 1, 10),
            ("safety:safe", 1, 10),
            ("safety:safe", 1, 20),
            ("safety:safe", 2, 10),
            ("safety:sketchy", 1, 10),
            ("safety:safe test", 1, 10),
            ("-safety:safe", 1, 10),
            ("-safety:safe", 1, 20),
            ("-safety:safe", 2, 10),
            ("-safety:sketchy", 1, 10),
            ("-safety:safe test", 1, 10),
            ("safety:safe -test", 1, 10),
            ("-test", 1, 10),
        ]
        for arg in args:
            executor.execute(*arg)
        assert len(hashes) == len(args)
        assert len(set(hashes)) == len(args)


@pytest.mark.parametrize(
    "input",
    [
        "special:fav",
        "special:liked",
        "special:disliked",
        "-special:fav",
        "-special:liked",
        "-special:disliked",
    ],
)
def test_putting_auth_dependent_queries_into_cache(user_factory, input):
    config = search.configs.PostSearchConfig()
    with unittest.mock.patch("szurubooru.func.cache.has"), unittest.mock.patch(
        "szurubooru.func.cache.put"
    ):
        hashes = []

        def appender(key, _value):
            hashes.append(key)

        cache.has.side_effect = lambda *args: False
        cache.put.side_effect = appender
        executor = search.Executor(config)

        executor.config.user = user_factory()
        executor.execute(input, 1, 1)
        assert len(set(hashes)) == 1

        executor.config.user = user_factory()
        executor.execute(input, 1, 1)
        assert len(set(hashes)) == 2

        executor.execute(input, 1, 1)
        assert len(set(hashes)) == 2
