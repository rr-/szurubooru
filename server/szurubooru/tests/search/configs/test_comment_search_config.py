from datetime import datetime

import pytest

from szurubooru import db, search


@pytest.fixture
def executor():
    return search.Executor(search.configs.CommentSearchConfig())


@pytest.fixture
def verify_unpaged(executor):
    def verify(input, expected_comment_text):
        actual_count, actual_comments = executor.execute(
            input, offset=0, limit=100
        )
        actual_comment_text = [c.text for c in actual_comments]
        assert actual_count == len(expected_comment_text)
        assert actual_comment_text == expected_comment_text

    return verify


@pytest.mark.parametrize(
    "input,expected_comment_text",
    [
        ("creation-time:2014", ["t2", "t1"]),
        ("creation-date:2014", ["t2", "t1"]),
    ],
)
def test_filter_by_creation_time(
    verify_unpaged, comment_factory, input, expected_comment_text
):
    comment1 = comment_factory(text="t1")
    comment2 = comment_factory(text="t2")
    comment3 = comment_factory(text="t3")
    comment1.creation_time = datetime(2014, 1, 1)
    comment2.creation_time = datetime(2014, 6, 1)
    comment3.creation_time = datetime(2015, 1, 1)
    db.session.add_all([comment1, comment2, comment3])
    db.session.flush()
    verify_unpaged(input, expected_comment_text)


@pytest.mark.parametrize(
    "input,expected_comment_text",
    [
        ("text:t1", ["t1"]),
        ("text:t2", ["t2"]),
        ("text:t1,t2", ["t1", "t2"]),
        ("text:t*", ["t1", "t2"]),
    ],
)
def test_filter_by_text(
    verify_unpaged, comment_factory, input, expected_comment_text
):
    comment1 = comment_factory(text="t1")
    comment2 = comment_factory(text="t2")
    db.session.add_all([comment1, comment2])
    db.session.flush()
    verify_unpaged(input, expected_comment_text)


@pytest.mark.parametrize(
    "input,expected_comment_text",
    [
        ("user:u1", ["t1"]),
        ("user:u2", ["t2"]),
        ("user:u1,u2", ["t2", "t1"]),
    ],
)
def test_filter_by_user(
    verify_unpaged, comment_factory, user_factory, input, expected_comment_text
):
    db.session.add(comment_factory(text="t2", user=user_factory(name="u2")))
    db.session.add(comment_factory(text="t1", user=user_factory(name="u1")))
    db.session.flush()
    verify_unpaged(input, expected_comment_text)


@pytest.mark.parametrize(
    "input,expected_comment_text",
    [
        ("post:1", ["t1"]),
        ("post:2", ["t2"]),
        ("post:1,2", ["t1", "t2"]),
    ],
)
def test_filter_by_post(
    verify_unpaged, comment_factory, post_factory, input, expected_comment_text
):
    db.session.add(comment_factory(text="t1", post=post_factory(id=1)))
    db.session.add(comment_factory(text="t2", post=post_factory(id=2)))
    db.session.flush()
    verify_unpaged(input, expected_comment_text)


@pytest.mark.parametrize(
    "input,expected_comment_text",
    [
        ("", ["t1", "t2"]),
        ("t1", ["t1"]),
        ("t2", ["t2"]),
        ("t1,t2", ["t1", "t2"]),
    ],
)
def test_anonymous(
    verify_unpaged, comment_factory, input, expected_comment_text
):
    db.session.add(comment_factory(text="t1"))
    db.session.add(comment_factory(text="t2"))
    db.session.flush()
    verify_unpaged(input, expected_comment_text)


@pytest.mark.parametrize(
    "input,expected_comment_text",
    [
        ("sort:user", ["t1", "t2"]),
    ],
)
def test_sort_by_user(
    verify_unpaged, comment_factory, user_factory, input, expected_comment_text
):
    db.session.add(comment_factory(text="t2", user=user_factory(name="u2")))
    db.session.add(comment_factory(text="t1", user=user_factory(name="u1")))
    db.session.flush()
    verify_unpaged(input, expected_comment_text)


@pytest.mark.parametrize(
    "input,expected_comment_text",
    [
        ("sort:post", ["t2", "t1"]),
    ],
)
def test_sort_by_post(
    verify_unpaged, comment_factory, post_factory, input, expected_comment_text
):
    db.session.add(comment_factory(text="t1", post=post_factory(id=1)))
    db.session.add(comment_factory(text="t2", post=post_factory(id=2)))
    db.session.flush()
    verify_unpaged(input, expected_comment_text)


@pytest.mark.parametrize(
    "input,expected_comment_text",
    [
        ("", ["t3", "t2", "t1"]),
        ("sort:creation-date", ["t3", "t2", "t1"]),
        ("sort:creation-time", ["t3", "t2", "t1"]),
    ],
)
def test_sort_by_creation_time(
    verify_unpaged, comment_factory, input, expected_comment_text
):
    comment1 = comment_factory(text="t1")
    comment2 = comment_factory(text="t2")
    comment3 = comment_factory(text="t3")
    comment1.creation_time = datetime(1991, 1, 1)
    comment2.creation_time = datetime(1991, 1, 2)
    comment3.creation_time = datetime(1991, 1, 3)
    db.session.add_all([comment3, comment1, comment2])
    db.session.flush()
    verify_unpaged(input, expected_comment_text)


@pytest.mark.parametrize(
    "input,expected_comment_text",
    [
        ("sort:last-edit-date", ["t3", "t2", "t1"]),
        ("sort:last-edit-time", ["t3", "t2", "t1"]),
        ("sort:edit-date", ["t3", "t2", "t1"]),
        ("sort:edit-time", ["t3", "t2", "t1"]),
    ],
)
def test_sort_by_last_edit_time(
    verify_unpaged, comment_factory, input, expected_comment_text
):
    comment1 = comment_factory(text="t1")
    comment2 = comment_factory(text="t2")
    comment3 = comment_factory(text="t3")
    comment1.last_edit_time = datetime(1991, 1, 1)
    comment2.last_edit_time = datetime(1991, 1, 2)
    comment3.last_edit_time = datetime(1991, 1, 3)
    db.session.add_all([comment3, comment1, comment2])
    db.session.flush()
    verify_unpaged(input, expected_comment_text)
