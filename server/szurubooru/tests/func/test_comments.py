from datetime import datetime
from unittest.mock import patch

import pytest

from szurubooru import db
from szurubooru.func import comments, users


def test_serialize_user(user_factory, comment_factory):
    with patch("szurubooru.func.users.get_avatar_url"):
        users.get_avatar_url.return_value = "https://example.com/avatar.png"
        comment = comment_factory(user=user_factory(name="dummy"))
        comment.comment_id = 77
        comment.creation_time = datetime(1997, 1, 1)
        comment.last_edit_time = datetime(1998, 1, 1)
        comment.text = "text"
        db.session.add(comment)
        db.session.flush()
        auth_user = user_factory()
        assert comments.serialize_comment(comment, auth_user) == {
            "id": comment.comment_id,
            "postId": comment.post.post_id,
            "creationTime": datetime(1997, 1, 1, 0, 0),
            "lastEditTime": datetime(1998, 1, 1, 0, 0),
            "score": 0,
            "ownScore": 0,
            "text": "text",
            "user": {
                "name": "dummy",
                "avatarUrl": "https://example.com/avatar.png",
            },
            "version": 1,
        }


def test_try_get_comment(comment_factory):
    comment = comment_factory()
    db.session.add(comment)
    db.session.flush()
    assert comments.try_get_comment_by_id(comment.comment_id + 1) is None
    assert comments.try_get_comment_by_id(comment.comment_id) is comment


def test_get_comment(comment_factory):
    comment = comment_factory()
    db.session.add(comment)
    db.session.flush()
    with pytest.raises(comments.CommentNotFoundError):
        comments.get_comment_by_id(comment.comment_id + 1)
    assert comments.get_comment_by_id(comment.comment_id) is comment


def test_create_comment(user_factory, post_factory, fake_datetime):
    user = user_factory()
    post = post_factory()
    db.session.add_all([user, post])
    with patch("szurubooru.func.comments.update_comment_text"), fake_datetime(
        "1997-01-01"
    ):
        comment = comments.create_comment(user, post, "text")
        assert comment.creation_time == datetime(1997, 1, 1)
        assert comment.user == user
        assert comment.post == post
        comments.update_comment_text.assert_called_once_with(comment, "text")


def test_update_comment_text_with_emptry_string(comment_factory):
    comment = comment_factory()
    with pytest.raises(comments.EmptyCommentTextError):
        comments.update_comment_text(comment, None)


def test_update_comment_text(comment_factory):
    comment = comment_factory()
    comments.update_comment_text(comment, "text")
    assert comment.text == "text"
