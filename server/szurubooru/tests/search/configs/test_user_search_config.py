from datetime import datetime

import pytest

from szurubooru import db, errors, search


@pytest.fixture
def executor():
    return search.Executor(search.configs.UserSearchConfig())


@pytest.fixture
def verify_unpaged(executor):
    def verify(input, expected_user_names):
        actual_count, actual_users = executor.execute(
            input, offset=0, limit=100
        )
        actual_user_names = [u.name for u in actual_users]
        assert actual_count == len(expected_user_names)
        assert actual_user_names == expected_user_names

    return verify


@pytest.mark.parametrize(
    "input,expected_user_names",
    [
        ("creation-time:2014", ["u1", "u2"]),
        ("creation-date:2014", ["u1", "u2"]),
        ("-creation-time:2014", ["u3"]),
        ("-creation-date:2014", ["u3"]),
        ("creation-time:2014..2014-06", ["u1", "u2"]),
        ("creation-time:2014-06..2015-01-01", ["u2", "u3"]),
        ("creation-time:2014-06..", ["u2", "u3"]),
        ("creation-time:..2014-06", ["u1", "u2"]),
        ("creation-time-min:2014-06", ["u2", "u3"]),
        ("creation-time-max:2014-06", ["u1", "u2"]),
        ("-creation-time:2014..2014-06", ["u3"]),
        ("-creation-time:2014-06..2015-01-01", ["u1"]),
        ("creation-date:2014..2014-06", ["u1", "u2"]),
        ("creation-date:2014-06..2015-01-01", ["u2", "u3"]),
        ("creation-date:2014-06..", ["u2", "u3"]),
        ("creation-date:..2014-06", ["u1", "u2"]),
        ("-creation-date:2014..2014-06", ["u3"]),
        ("-creation-date:2014-06..2015-01-01", ["u1"]),
        ("creation-time:2014-01,2015", ["u1", "u3"]),
        ("creation-date:2014-01,2015", ["u1", "u3"]),
        ("-creation-time:2014-01,2015", ["u2"]),
        ("-creation-date:2014-01,2015", ["u2"]),
    ],
)
def test_filter_by_creation_time(
    verify_unpaged, input, expected_user_names, user_factory
):
    user1 = user_factory(name="u1")
    user2 = user_factory(name="u2")
    user3 = user_factory(name="u3")
    user1.creation_time = datetime(2014, 1, 1)
    user2.creation_time = datetime(2014, 6, 1)
    user3.creation_time = datetime(2015, 1, 1)
    db.session.add_all([user1, user2, user3])
    db.session.flush()
    verify_unpaged(input, expected_user_names)


@pytest.mark.parametrize(
    "input,expected_user_names",
    [
        ("name:user1", ["user1"]),
        ("name:user2", ["user2"]),
        ("name:none", []),
        ("name:", []),
        ("name:*1", ["user1"]),
        ("name:*2", ["user2"]),
        ("name:*", ["user1", "user2", "user3"]),
        ("name:u*", ["user1", "user2", "user3"]),
        ("name:*ser*", ["user1", "user2", "user3"]),
        ("name:*zer*", []),
        ("name:zer*", []),
        ("name:*zer", []),
        ("-name:user1", ["user2", "user3"]),
        ("-name:user2", ["user1", "user3"]),
        ("name:user1,user2", ["user1", "user2"]),
        ("-name:user1,user3", ["user2"]),
    ],
)
def test_filter_by_name(
    verify_unpaged, input, expected_user_names, user_factory
):
    db.session.add(user_factory(name="user1"))
    db.session.add(user_factory(name="user2"))
    db.session.add(user_factory(name="user3"))
    db.session.flush()
    verify_unpaged(input, expected_user_names)


@pytest.mark.parametrize(
    "input,expected_user_names",
    [
        ("name:u1", ["u1"]),
        ("name:u2*", ["u2.."]),
        ("name:u1,u3..x", ["u1", "u3..x"]),
        ("name:u2..", None),
        ("name:*..*", None),
        ("name:u3..x", None),
        ("name:*..x", None),
        ("name:u2\\..", ["u2.."]),
        ("name:*\\..*", ["u2..", "u3..x"]),
        ("name:u3\\..x", ["u3..x"]),
        ("name:*\\..x", ["u3..x"]),
        ("name:u2.\\.", ["u2.."]),
        ("name:*.\\.*", ["u2..", "u3..x"]),
        ("name:u3.\\.x", ["u3..x"]),
        ("name:*.\\.x", ["u3..x"]),
        ("name:u2\\.\\.", ["u2.."]),
        ("name:*\\.\\.*", ["u2..", "u3..x"]),
        ("name:u3\\.\\.x", ["u3..x"]),
        ("name:*\\.\\.x", ["u3..x"]),
    ],
)
def test_filter_by_name_that_looks_like_range(
    verify_unpaged, input, expected_user_names, user_factory
):
    db.session.add(user_factory(name="u1"))
    db.session.add(user_factory(name="u2.."))
    db.session.add(user_factory(name="u3..x"))
    db.session.flush()
    if not expected_user_names:
        with pytest.raises(errors.SearchError):
            verify_unpaged(input, expected_user_names)
    else:
        verify_unpaged(input, expected_user_names)


@pytest.mark.parametrize(
    "input,expected_user_names",
    [
        ("", ["u1", "u2"]),
        ("u1", ["u1"]),
        ("u2", ["u2"]),
        ("u1,u2", ["u1", "u2"]),
    ],
)
def test_anonymous(verify_unpaged, input, expected_user_names, user_factory):
    db.session.add(user_factory(name="u1"))
    db.session.add(user_factory(name="u2"))
    db.session.flush()
    verify_unpaged(input, expected_user_names)


@pytest.mark.parametrize(
    "input,expected_user_names",
    [
        ("creation-time:2014 u1", ["u1"]),
        ("creation-time:2014 u2", ["u2"]),
        ("creation-time:2016 u2", []),
    ],
)
def test_combining_tokens(
    verify_unpaged, input, expected_user_names, user_factory
):
    user1 = user_factory(name="u1")
    user2 = user_factory(name="u2")
    user3 = user_factory(name="u3")
    user1.creation_time = datetime(2014, 1, 1)
    user2.creation_time = datetime(2014, 6, 1)
    user3.creation_time = datetime(2015, 1, 1)
    db.session.add_all([user1, user2, user3])
    db.session.flush()
    verify_unpaged(input, expected_user_names)


@pytest.mark.parametrize(
    "offset,limit,expected_total_count,expected_user_names",
    [
        (0, 1, 2, ["u1"]),
        (1, 1, 2, ["u2"]),
        (2, 1, 2, []),
        (-1, 1, 2, []),
        (-1, 2, 2, ["u1"]),
        (0, 2, 2, ["u1", "u2"]),
        (3, 1, 2, []),
        (0, 0, 2, []),
    ],
)
def test_paging(
    executor,
    user_factory,
    offset,
    limit,
    expected_total_count,
    expected_user_names,
):
    db.session.add(user_factory(name="u1"))
    db.session.add(user_factory(name="u2"))
    db.session.flush()
    actual_count, actual_users = executor.execute(
        "", offset=offset, limit=limit
    )
    actual_user_names = [u.name for u in actual_users]
    assert actual_count == expected_total_count
    assert actual_user_names == expected_user_names


@pytest.mark.parametrize(
    "input,expected_user_names",
    [
        ("", ["u1", "u2"]),
        ("sort:name", ["u1", "u2"]),
        ("-sort:name", ["u2", "u1"]),
        ("sort:name,asc", ["u1", "u2"]),
        ("sort:name,desc", ["u2", "u1"]),
        ("-sort:name,asc", ["u2", "u1"]),
        ("-sort:name,desc", ["u1", "u2"]),
    ],
)
def test_sort_by_name(
    verify_unpaged, input, expected_user_names, user_factory
):
    db.session.add(user_factory(name="u2"))
    db.session.add(user_factory(name="u1"))
    db.session.flush()
    verify_unpaged(input, expected_user_names)


@pytest.mark.parametrize(
    "input,expected_user_names",
    [
        ("", ["u1", "u2", "u3"]),
        ("sort:creation-date", ["u3", "u2", "u1"]),
        ("sort:creation-time", ["u3", "u2", "u1"]),
        ("-sort:creation-date", ["u1", "u2", "u3"]),
        ("sort:creation-date,asc", ["u1", "u2", "u3"]),
        ("sort:creation-date,desc", ["u3", "u2", "u1"]),
        ("-sort:creation-date,asc", ["u3", "u2", "u1"]),
        ("-sort:creation-date,desc", ["u1", "u2", "u3"]),
    ],
)
def test_sort_by_creation_time(
    verify_unpaged, input, expected_user_names, user_factory
):
    user1 = user_factory(name="u1")
    user2 = user_factory(name="u2")
    user3 = user_factory(name="u3")
    user1.creation_time = datetime(1991, 1, 1)
    user2.creation_time = datetime(1991, 1, 2)
    user3.creation_time = datetime(1991, 1, 3)
    db.session.add_all([user3, user1, user2])
    db.session.flush()
    verify_unpaged(input, expected_user_names)


@pytest.mark.parametrize(
    "input,expected_user_names",
    [
        ("", ["u1", "u2", "u3"]),
        ("sort:last-login-date", ["u3", "u2", "u1"]),
        ("sort:last-login-time", ["u3", "u2", "u1"]),
        ("sort:login-date", ["u3", "u2", "u1"]),
        ("sort:login-time", ["u3", "u2", "u1"]),
    ],
)
def test_sort_by_last_login_time(
    verify_unpaged, input, expected_user_names, user_factory
):
    user1 = user_factory(name="u1")
    user2 = user_factory(name="u2")
    user3 = user_factory(name="u3")
    user1.last_login_time = datetime(1991, 1, 1)
    user2.last_login_time = datetime(1991, 1, 2)
    user3.last_login_time = datetime(1991, 1, 3)
    db.session.add_all([user3, user1, user2])
    db.session.flush()
    verify_unpaged(input, expected_user_names)


def test_random_sort(executor, user_factory):
    user1 = user_factory(name="u1")
    user2 = user_factory(name="u2")
    user3 = user_factory(name="u3")
    db.session.add_all([user3, user1, user2])
    db.session.flush()
    actual_count, actual_users = executor.execute(
        "sort:random", offset=0, limit=100
    )
    actual_user_names = [u.name for u in actual_users]
    assert actual_count == 3
    assert len(actual_user_names) == 3
    assert "u1" in actual_user_names
    assert "u2" in actual_user_names
    assert "u3" in actual_user_names


@pytest.mark.parametrize(
    "input,expected_error",
    [
        ("creation-date:..", errors.SearchError),
        ("creation-date-min:..", errors.ValidationError),
        ("creation-date-min:..2014-01-01", errors.ValidationError),
        ("creation-date-min:2014-01-01..", errors.ValidationError),
        ("creation-date-max:..2014-01-01", errors.ValidationError),
        ("creation-date-max:2014-01-01..", errors.ValidationError),
        ("creation-date-max:yesterday,today", errors.ValidationError),
        ("creation-date:bad..", errors.ValidationError),
        ("creation-date:..bad", errors.ValidationError),
        ("creation-date:bad..bad", errors.ValidationError),
        ("sort:", errors.SearchError),
        ("sort:nam", errors.SearchError),
        ("sort:name,as", errors.SearchError),
        ("sort:name,asc,desc", errors.SearchError),
        ("bad:x", errors.SearchError),
        ("special:unsupported", errors.SearchError),
    ],
)
def test_bad_tokens(executor, input, expected_error):
    with pytest.raises(expected_error):
        executor.execute(input, offset=0, limit=100)
