from datetime import datetime

import pytest

from szurubooru import db, errors, search


@pytest.fixture
def executor():
    return search.Executor(search.configs.PoolSearchConfig())


@pytest.fixture
def verify_unpaged(executor):
    def verify(input, expected_pool_names):
        actual_count, actual_pools = executor.execute(
            input, offset=0, limit=100
        )
        actual_pool_names = [u.names[0].name for u in actual_pools]
        assert actual_count == len(expected_pool_names)
        assert actual_pool_names == expected_pool_names

    return verify


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("", ["t1", "t2"]),
        ("t1", ["t1"]),
        ("t2", ["t2"]),
        ("t1,t2", ["t1", "t2"]),
        ("T1,T2", ["t1", "t2"]),
    ],
)
def test_filter_anonymous(
    verify_unpaged, pool_factory, input, expected_pool_names
):
    db.session.add(pool_factory(id=1, names=["t1"]))
    db.session.add(pool_factory(id=2, names=["t2"]))
    db.session.flush()
    verify_unpaged(input, expected_pool_names)


@pytest.mark.parametrize(
    "db_driver,input,expected_pool_names",
    [
        (None, ",", None),
        (None, "t1,", None),
        (None, "t1,t2", ["t1", "t2"]),
        (None, "t1\\,", []),
        (None, "asd..asd", None),
        (None, "asd\\..asd", []),
        (None, "asd.\\.asd", []),
        (None, "asd\\.\\.asd", []),
        (None, "-", None),
        (None, "\\-", ["-"]),
        (
            None,
            "--",
            [
                "t1",
                "t2",
                "*",
                "*asd*",
                ":",
                "asd:asd",
                "\\",
                "\\asd",
                "-asd",
            ],
        ),
        (None, "\\--", []),
        (
            None,
            "-\\-",
            [
                "t1",
                "t2",
                "*",
                "*asd*",
                ":",
                "asd:asd",
                "\\",
                "\\asd",
                "-asd",
            ],
        ),
        (None, "-*", []),
        (None, "\\-*", ["-", "-asd"]),
        (None, ":", None),
        (None, "\\:", [":"]),
        (None, "\\:asd", []),
        (None, "*\\:*", [":", "asd:asd"]),
        (None, "asd:asd", None),
        (None, "asd\\:asd", ["asd:asd"]),
        (
            None,
            "*",
            [
                "t1",
                "t2",
                "*",
                "*asd*",
                ":",
                "asd:asd",
                "\\",
                "\\asd",
                "-",
                "-asd",
            ],
        ),
        (None, "\\*", ["*"]),
        (None, "\\", None),
        (None, "\\asd", None),
        ("psycopg2", "\\\\", ["\\"]),
        ("psycopg2", "\\\\asd", ["\\asd"]),
    ],
)
def test_escaping(
    executor, pool_factory, input, expected_pool_names, db_driver
):
    db.session.add_all(
        [
            pool_factory(id=1, names=["t1"]),
            pool_factory(id=2, names=["t2"]),
            pool_factory(id=3, names=["*"]),
            pool_factory(id=4, names=["*asd*"]),
            pool_factory(id=5, names=[":"]),
            pool_factory(id=6, names=["asd:asd"]),
            pool_factory(id=7, names=["\\"]),
            pool_factory(id=8, names=["\\asd"]),
            pool_factory(id=9, names=["-"]),
            pool_factory(id=10, names=["-asd"]),
        ]
    )
    db.session.flush()

    if expected_pool_names is None:
        with pytest.raises(errors.SearchError):
            executor.execute(input, offset=0, limit=100)
    else:
        actual_count, actual_pools = executor.execute(
            input, offset=0, limit=100
        )
        actual_pool_names = [u.names[0].name for u in actual_pools]
        assert actual_count == len(expected_pool_names)
        assert sorted(actual_pool_names) == sorted(expected_pool_names)


def test_filter_anonymous_starting_with_colon(verify_unpaged, pool_factory):
    db.session.add(pool_factory(id=1, names=[":t"]))
    db.session.flush()
    with pytest.raises(errors.SearchError):
        verify_unpaged(":t", [":t"])
    verify_unpaged("\\:t", [":t"])


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("name:pool1", ["pool1"]),
        ("name:pool2", ["pool2"]),
        ("name:none", []),
        ("name:", []),
        ("name:*1", ["pool1"]),
        ("name:*2", ["pool2"]),
        ("name:*", ["pool1", "pool2", "pool3", "pool4"]),
        ("name:p*", ["pool1", "pool2", "pool3", "pool4"]),
        ("name:*o*", ["pool1", "pool2", "pool3", "pool4"]),
        ("name:*!*", []),
        ("name:!*", []),
        ("name:*!", []),
        ("-name:pool1", ["pool2", "pool3", "pool4"]),
        ("-name:pool2", ["pool1", "pool3", "pool4"]),
        ("name:pool1,pool2", ["pool1", "pool2"]),
        ("-name:pool1,pool3", ["pool2", "pool4"]),
        ("name:pool4", ["pool4"]),
        ("name:pool5", ["pool4"]),
        ("name:pool4,pool5", ["pool4"]),
    ],
)
def test_filter_by_name(
    verify_unpaged, pool_factory, input, expected_pool_names
):
    db.session.add(pool_factory(id=1, names=["pool1"]))
    db.session.add(pool_factory(id=2, names=["pool2"]))
    db.session.add(pool_factory(id=3, names=["pool3"]))
    db.session.add(pool_factory(id=4, names=["pool4", "pool5", "pool6"]))
    db.session.flush()
    verify_unpaged(input, expected_pool_names)


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("category:cat1", ["t1", "t2"]),
        ("category:cat2", ["t3"]),
        ("category:cat1,cat2", ["t1", "t2", "t3"]),
    ],
)
def test_filter_by_category(
    verify_unpaged,
    pool_factory,
    pool_category_factory,
    input,
    expected_pool_names,
):
    cat1 = pool_category_factory(name="cat1")
    cat2 = pool_category_factory(name="cat2")
    pool1 = pool_factory(id=1, names=["t1"], category=cat1)
    pool2 = pool_factory(id=2, names=["t2"], category=cat1)
    pool3 = pool_factory(id=3, names=["t3"], category=cat2)
    db.session.add_all([pool1, pool2, pool3])
    db.session.flush()
    verify_unpaged(input, expected_pool_names)


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("creation-time:2014", ["t1", "t2"]),
        ("creation-date:2014", ["t1", "t2"]),
        ("-creation-time:2014", ["t3"]),
        ("-creation-date:2014", ["t3"]),
        ("creation-time:2014..2014-06", ["t1", "t2"]),
        ("creation-time:2014-06..2015-01-01", ["t2", "t3"]),
        ("creation-time:2014-06..", ["t2", "t3"]),
        ("creation-time:..2014-06", ["t1", "t2"]),
        ("-creation-time:2014..2014-06", ["t3"]),
        ("-creation-time:2014-06..2015-01-01", ["t1"]),
        ("creation-date:2014..2014-06", ["t1", "t2"]),
        ("creation-date:2014-06..2015-01-01", ["t2", "t3"]),
        ("creation-date:2014-06..", ["t2", "t3"]),
        ("creation-date:..2014-06", ["t1", "t2"]),
        ("-creation-date:2014..2014-06", ["t3"]),
        ("-creation-date:2014-06..2015-01-01", ["t1"]),
        ("creation-time:2014-01,2015", ["t1", "t3"]),
        ("creation-date:2014-01,2015", ["t1", "t3"]),
        ("-creation-time:2014-01,2015", ["t2"]),
        ("-creation-date:2014-01,2015", ["t2"]),
    ],
)
def test_filter_by_creation_time(
    verify_unpaged, pool_factory, input, expected_pool_names
):
    pool1 = pool_factory(id=1, names=["t1"])
    pool2 = pool_factory(id=2, names=["t2"])
    pool3 = pool_factory(id=3, names=["t3"])
    pool1.creation_time = datetime(2014, 1, 1)
    pool2.creation_time = datetime(2014, 6, 1)
    pool3.creation_time = datetime(2015, 1, 1)
    db.session.add_all([pool1, pool2, pool3])
    db.session.flush()
    verify_unpaged(input, expected_pool_names)


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("last-edit-date:2014", ["t1", "t3"]),
        ("last-edit-time:2014", ["t1", "t3"]),
        ("edit-date:2014", ["t1", "t3"]),
        ("edit-time:2014", ["t1", "t3"]),
    ],
)
def test_filter_by_edit_time(
    verify_unpaged, pool_factory, input, expected_pool_names
):
    pool1 = pool_factory(id=1, names=["t1"])
    pool2 = pool_factory(id=2, names=["t2"])
    pool3 = pool_factory(id=3, names=["t3"])
    pool1.last_edit_time = datetime(2014, 1, 1)
    pool2.last_edit_time = datetime(2015, 1, 1)
    pool3.last_edit_time = datetime(2014, 1, 1)
    db.session.add_all([pool1, pool2, pool3])
    db.session.flush()
    verify_unpaged(input, expected_pool_names)


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("post-count:2", ["t1"]),
        ("post-count:1", ["t2"]),
        ("post-count:1..", ["t1", "t2"]),
        ("post-count-min:1", ["t1", "t2"]),
        ("post-count:..1", ["t2"]),
        ("post-count-max:1", ["t2"]),
    ],
)
def test_filter_by_post_count(
    verify_unpaged, pool_factory, post_factory, input, expected_pool_names
):
    post1 = post_factory(id=1)
    post2 = post_factory(id=2)
    pool1 = pool_factory(id=1, names=["t1"])
    pool2 = pool_factory(id=2, names=["t2"])
    db.session.add_all([post1, post2, pool1, pool2])
    pool1.posts.append(post1)
    pool1.posts.append(post2)
    pool2.posts.append(post1)
    db.session.flush()
    verify_unpaged(input, expected_pool_names)


@pytest.mark.parametrize(
    "input",
    [
        "post-count:..",
        "post-count:asd",
        "post-count:asd,1",
        "post-count:1,asd",
        "post-count:asd..1",
        "post-count:1..asd",
    ],
)
def test_filter_by_invalid_input(executor, input):
    with pytest.raises(errors.SearchError):
        executor.execute(input, offset=0, limit=100)


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("", ["t1", "t2"]),
        ("sort:name", ["t1", "t2"]),
        ("-sort:name", ["t2", "t1"]),
        ("sort:name,asc", ["t1", "t2"]),
        ("sort:name,desc", ["t2", "t1"]),
        ("-sort:name,asc", ["t2", "t1"]),
        ("-sort:name,desc", ["t1", "t2"]),
    ],
)
def test_sort_by_name(
    verify_unpaged, pool_factory, input, expected_pool_names
):
    db.session.add(pool_factory(id=2, names=["t2"]))
    db.session.add(pool_factory(id=1, names=["t1"]))
    db.session.flush()
    verify_unpaged(input, expected_pool_names)


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("", ["t1", "t2", "t3"]),
        ("sort:creation-date", ["t3", "t2", "t1"]),
        ("sort:creation-time", ["t3", "t2", "t1"]),
    ],
)
def test_sort_by_creation_time(
    verify_unpaged, pool_factory, input, expected_pool_names
):
    pool1 = pool_factory(id=1, names=["t1"])
    pool2 = pool_factory(id=2, names=["t2"])
    pool3 = pool_factory(id=3, names=["t3"])
    pool1.creation_time = datetime(1991, 1, 1)
    pool2.creation_time = datetime(1991, 1, 2)
    pool3.creation_time = datetime(1991, 1, 3)
    db.session.add_all([pool3, pool1, pool2])
    db.session.flush()
    verify_unpaged(input, expected_pool_names)


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("", ["t1", "t2", "t3"]),
        ("sort:last-edit-date", ["t3", "t2", "t1"]),
        ("sort:last-edit-time", ["t3", "t2", "t1"]),
        ("sort:edit-date", ["t3", "t2", "t1"]),
        ("sort:edit-time", ["t3", "t2", "t1"]),
    ],
)
def test_sort_by_last_edit_time(
    verify_unpaged, pool_factory, input, expected_pool_names
):
    pool1 = pool_factory(id=1, names=["t1"])
    pool2 = pool_factory(id=2, names=["t2"])
    pool3 = pool_factory(id=3, names=["t3"])
    pool1.last_edit_time = datetime(1991, 1, 1)
    pool2.last_edit_time = datetime(1991, 1, 2)
    pool3.last_edit_time = datetime(1991, 1, 3)
    db.session.add_all([pool3, pool1, pool2])
    db.session.flush()
    verify_unpaged(input, expected_pool_names)


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("sort:post-count", ["t2", "t1"]),
    ],
)
def test_sort_by_post_count(
    verify_unpaged, pool_factory, post_factory, input, expected_pool_names
):
    post1 = post_factory(id=1)
    post2 = post_factory(id=2)
    pool1 = pool_factory(id=1, names=["t1"])
    pool2 = pool_factory(id=2, names=["t2"])
    db.session.add_all([post1, post2, pool1, pool2])
    pool1.posts.append(post1)
    pool2.posts.append(post1)
    pool2.posts.append(post2)
    db.session.flush()
    verify_unpaged(input, expected_pool_names)


@pytest.mark.parametrize(
    "input,expected_pool_names",
    [
        ("sort:category", ["t3", "t1", "t2"]),
    ],
)
def test_sort_by_category(
    verify_unpaged,
    pool_factory,
    pool_category_factory,
    input,
    expected_pool_names,
):
    cat1 = pool_category_factory(name="cat1")
    cat2 = pool_category_factory(name="cat2")
    pool1 = pool_factory(id=1, names=["t1"], category=cat2)
    pool2 = pool_factory(id=2, names=["t2"], category=cat2)
    pool3 = pool_factory(id=3, names=["t3"], category=cat1)
    db.session.add_all([pool1, pool2, pool3])
    db.session.flush()
    verify_unpaged(input, expected_pool_names)
