from datetime import datetime

import pytest

from szurubooru import db, errors, search


@pytest.fixture
def executor():
    return search.Executor(search.configs.TagSearchConfig())


@pytest.fixture
def verify_unpaged(executor):
    def verify(input, expected_tag_names):
        actual_count, actual_tags = executor.execute(
            input, offset=0, limit=100
        )
        actual_tag_names = [u.names[0].name for u in actual_tags]
        assert actual_count == len(expected_tag_names)
        assert actual_tag_names == expected_tag_names

    return verify


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("", ["t1", "t2"]),
        ("t1", ["t1"]),
        ("t2", ["t2"]),
        ("t1,t2", ["t1", "t2"]),
        ("T1,T2", ["t1", "t2"]),
    ],
)
def test_filter_anonymous(
    verify_unpaged, tag_factory, input, expected_tag_names
):
    db.session.add(tag_factory(names=["t1"]))
    db.session.add(tag_factory(names=["t2"]))
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "db_driver,input,expected_tag_names",
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
def test_escaping(executor, tag_factory, input, expected_tag_names, db_driver):
    db.session.add_all(
        [
            tag_factory(names=["t1"]),
            tag_factory(names=["t2"]),
            tag_factory(names=["*"]),
            tag_factory(names=["*asd*"]),
            tag_factory(names=[":"]),
            tag_factory(names=["asd:asd"]),
            tag_factory(names=["\\"]),
            tag_factory(names=["\\asd"]),
            tag_factory(names=["-"]),
            tag_factory(names=["-asd"]),
        ]
    )
    db.session.flush()

    if expected_tag_names is None:
        with pytest.raises(errors.SearchError):
            executor.execute(input, offset=0, limit=100)
    else:
        actual_count, actual_tags = executor.execute(
            input, offset=0, limit=100
        )
        actual_tag_names = [u.names[0].name for u in actual_tags]
        assert actual_count == len(expected_tag_names)
        assert sorted(actual_tag_names) == sorted(expected_tag_names)


def test_filter_anonymous_starting_with_colon(verify_unpaged, tag_factory):
    db.session.add(tag_factory(names=[":t"]))
    db.session.flush()
    with pytest.raises(errors.SearchError):
        verify_unpaged(":t", [":t"])
    verify_unpaged("\\:t", [":t"])


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("name:tag1", ["tag1"]),
        ("name:tag2", ["tag2"]),
        ("name:none", []),
        ("name:", []),
        ("name:*1", ["tag1"]),
        ("name:*2", ["tag2"]),
        ("name:*", ["tag1", "tag2", "tag3", "tag4"]),
        ("name:t*", ["tag1", "tag2", "tag3", "tag4"]),
        ("name:*a*", ["tag1", "tag2", "tag3", "tag4"]),
        ("name:*!*", []),
        ("name:!*", []),
        ("name:*!", []),
        ("-name:tag1", ["tag2", "tag3", "tag4"]),
        ("-name:tag2", ["tag1", "tag3", "tag4"]),
        ("name:tag1,tag2", ["tag1", "tag2"]),
        ("-name:tag1,tag3", ["tag2", "tag4"]),
        ("name:tag4", ["tag4"]),
        ("name:tag5", ["tag4"]),
        ("name:tag4,tag5", ["tag4"]),
    ],
)
def test_filter_by_name(
    verify_unpaged, tag_factory, input, expected_tag_names
):
    db.session.add(tag_factory(names=["tag1"]))
    db.session.add(tag_factory(names=["tag2"]))
    db.session.add(tag_factory(names=["tag3"]))
    db.session.add(tag_factory(names=["tag4", "tag5", "tag6"]))
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("category:cat1", ["t1", "t2"]),
        ("category:cat2", ["t3"]),
        ("category:cat1,cat2", ["t1", "t2", "t3"]),
    ],
)
def test_filter_by_category(
    verify_unpaged,
    tag_factory,
    tag_category_factory,
    input,
    expected_tag_names,
):
    cat1 = tag_category_factory(name="cat1")
    cat2 = tag_category_factory(name="cat2")
    tag1 = tag_factory(names=["t1"], category=cat1)
    tag2 = tag_factory(names=["t2"], category=cat1)
    tag3 = tag_factory(names=["t3"], category=cat2)
    db.session.add_all([tag1, tag2, tag3])
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
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
    verify_unpaged, tag_factory, input, expected_tag_names
):
    tag1 = tag_factory(names=["t1"])
    tag2 = tag_factory(names=["t2"])
    tag3 = tag_factory(names=["t3"])
    tag1.creation_time = datetime(2014, 1, 1)
    tag2.creation_time = datetime(2014, 6, 1)
    tag3.creation_time = datetime(2015, 1, 1)
    db.session.add_all([tag1, tag2, tag3])
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("last-edit-date:2014", ["t1", "t3"]),
        ("last-edit-time:2014", ["t1", "t3"]),
        ("edit-date:2014", ["t1", "t3"]),
        ("edit-time:2014", ["t1", "t3"]),
    ],
)
def test_filter_by_edit_time(
    verify_unpaged, tag_factory, input, expected_tag_names
):
    tag1 = tag_factory(names=["t1"])
    tag2 = tag_factory(names=["t2"])
    tag3 = tag_factory(names=["t3"])
    tag1.last_edit_time = datetime(2014, 1, 1)
    tag2.last_edit_time = datetime(2015, 1, 1)
    tag3.last_edit_time = datetime(2014, 1, 1)
    db.session.add_all([tag1, tag2, tag3])
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("post-count:2", ["t1"]),
        ("post-count:1", ["t2"]),
        ("post-count:1..", ["t1", "t2"]),
        ("post-count-min:1", ["t1", "t2"]),
        ("post-count:..1", ["t2"]),
        ("post-count-max:1", ["t2"]),
        ("usage-count:2", ["t1"]),
        ("usage-count:1", ["t2"]),
        ("usages:2", ["t1"]),
        ("usages:1", ["t2"]),
    ],
)
def test_filter_by_post_count(
    verify_unpaged, tag_factory, post_factory, input, expected_tag_names
):
    post1 = post_factory()
    post2 = post_factory()
    tag1 = tag_factory(names=["t1"])
    tag2 = tag_factory(names=["t2"])
    db.session.add_all([post1, post2, tag1, tag2])
    post1.tags.append(tag1)
    post1.tags.append(tag2)
    post2.tags.append(tag1)
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


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
    "input,expected_tag_names",
    [
        ("suggestion-count:2", ["t1"]),
        ("suggestion-count:1", ["t2"]),
        ("suggestion-count:0", ["sug1", "sug2", "sug3"]),
    ],
)
def test_filter_by_suggestion_count(
    verify_unpaged, tag_factory, input, expected_tag_names
):
    sug1 = tag_factory(names=["sug1"])
    sug2 = tag_factory(names=["sug2"])
    sug3 = tag_factory(names=["sug3"])
    tag1 = tag_factory(names=["t1"])
    tag2 = tag_factory(names=["t2"])
    db.session.add_all([sug1, sug3, tag2, sug2, tag1])
    tag1.suggestions.append(sug1)
    tag1.suggestions.append(sug2)
    tag2.suggestions.append(sug3)
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("implication-count:2", ["t1"]),
        ("implication-count:1", ["t2"]),
        ("implication-count:0", ["sug1", "sug2", "sug3"]),
    ],
)
def test_filter_by_implication_count(
    verify_unpaged, tag_factory, input, expected_tag_names
):
    sug1 = tag_factory(names=["sug1"])
    sug2 = tag_factory(names=["sug2"])
    sug3 = tag_factory(names=["sug3"])
    tag1 = tag_factory(names=["t1"])
    tag2 = tag_factory(names=["t2"])
    db.session.add_all([sug1, sug3, tag2, sug2, tag1])
    tag1.implications.append(sug1)
    tag1.implications.append(sug2)
    tag2.implications.append(sug3)
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
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
def test_sort_by_name(verify_unpaged, tag_factory, input, expected_tag_names):
    db.session.add(tag_factory(names=["t2"]))
    db.session.add(tag_factory(names=["t1"]))
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("", ["t1", "t2", "t3"]),
        ("sort:creation-date", ["t3", "t2", "t1"]),
        ("sort:creation-time", ["t3", "t2", "t1"]),
    ],
)
def test_sort_by_creation_time(
    verify_unpaged, tag_factory, input, expected_tag_names
):
    tag1 = tag_factory(names=["t1"])
    tag2 = tag_factory(names=["t2"])
    tag3 = tag_factory(names=["t3"])
    tag1.creation_time = datetime(1991, 1, 1)
    tag2.creation_time = datetime(1991, 1, 2)
    tag3.creation_time = datetime(1991, 1, 3)
    db.session.add_all([tag3, tag1, tag2])
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("", ["t1", "t2", "t3"]),
        ("sort:last-edit-date", ["t3", "t2", "t1"]),
        ("sort:last-edit-time", ["t3", "t2", "t1"]),
        ("sort:edit-date", ["t3", "t2", "t1"]),
        ("sort:edit-time", ["t3", "t2", "t1"]),
    ],
)
def test_sort_by_last_edit_time(
    verify_unpaged, tag_factory, input, expected_tag_names
):
    tag1 = tag_factory(names=["t1"])
    tag2 = tag_factory(names=["t2"])
    tag3 = tag_factory(names=["t3"])
    tag1.last_edit_time = datetime(1991, 1, 1)
    tag2.last_edit_time = datetime(1991, 1, 2)
    tag3.last_edit_time = datetime(1991, 1, 3)
    db.session.add_all([tag3, tag1, tag2])
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("sort:post-count", ["t2", "t1"]),
        ("sort:usage-count", ["t2", "t1"]),
        ("sort:usages", ["t2", "t1"]),
    ],
)
def test_sort_by_post_count(
    verify_unpaged, tag_factory, post_factory, input, expected_tag_names
):
    post1 = post_factory()
    post2 = post_factory()
    tag1 = tag_factory(names=["t1"])
    tag2 = tag_factory(names=["t2"])
    db.session.add_all([post1, post2, tag1, tag2])
    post1.tags.append(tag1)
    post1.tags.append(tag2)
    post2.tags.append(tag2)
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("sort:suggestion-count", ["t1", "t2", "sug1", "sug2", "sug3"]),
    ],
)
def test_sort_by_suggestion_count(
    verify_unpaged, tag_factory, input, expected_tag_names
):
    sug1 = tag_factory(names=["sug1"])
    sug2 = tag_factory(names=["sug2"])
    sug3 = tag_factory(names=["sug3"])
    tag1 = tag_factory(names=["t1"])
    tag2 = tag_factory(names=["t2"])
    db.session.add_all([sug1, sug3, tag2, sug2, tag1])
    tag1.suggestions.append(sug1)
    tag1.suggestions.append(sug2)
    tag2.suggestions.append(sug3)
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("sort:implication-count", ["t1", "t2", "sug1", "sug2", "sug3"]),
    ],
)
def test_sort_by_implication_count(
    verify_unpaged, tag_factory, input, expected_tag_names
):
    sug1 = tag_factory(names=["sug1"])
    sug2 = tag_factory(names=["sug2"])
    sug3 = tag_factory(names=["sug3"])
    tag1 = tag_factory(names=["t1"])
    tag2 = tag_factory(names=["t2"])
    db.session.add_all([sug1, sug3, tag2, sug2, tag1])
    tag1.implications.append(sug1)
    tag1.implications.append(sug2)
    tag2.implications.append(sug3)
    db.session.flush()
    verify_unpaged(input, expected_tag_names)


@pytest.mark.parametrize(
    "input,expected_tag_names",
    [
        ("sort:category", ["t3", "t1", "t2"]),
    ],
)
def test_sort_by_category(
    verify_unpaged,
    tag_factory,
    tag_category_factory,
    input,
    expected_tag_names,
):
    cat1 = tag_category_factory(name="cat1")
    cat2 = tag_category_factory(name="cat2")
    tag1 = tag_factory(names=["t1"], category=cat2)
    tag2 = tag_factory(names=["t2"], category=cat2)
    tag3 = tag_factory(names=["t3"], category=cat1)
    db.session.add_all([tag1, tag2, tag3])
    db.session.flush()
    verify_unpaged(input, expected_tag_names)
