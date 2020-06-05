from datetime import datetime

from szurubooru import db, model


def test_saving_comment(user_factory, post_factory):
    user = user_factory()
    post = post_factory()
    comment = model.Comment()
    comment.text = "long text" * 1000
    comment.user = user
    comment.post = post
    comment.creation_time = datetime(1997, 1, 1)
    comment.last_edit_time = datetime(1998, 1, 1)
    db.session.add_all([user, post, comment])
    db.session.commit()

    db.session.refresh(comment)
    assert not db.session.dirty
    assert comment.user is not None and comment.user.user_id is not None
    assert comment.text == "long text" * 1000
    assert comment.creation_time == datetime(1997, 1, 1)
    assert comment.last_edit_time == datetime(1998, 1, 1)


def test_cascade_deletions(comment_factory, user_factory, post_factory):
    user = user_factory()
    post = post_factory()
    comment = comment_factory(user=user, post=post)
    db.session.add_all([user, comment])
    db.session.flush()

    score = model.CommentScore()
    score.comment = comment
    score.user = user
    score.time = datetime(1997, 1, 1)
    score.score = 1
    db.session.add(score)
    db.session.flush()

    assert not db.session.dirty
    assert comment.user is not None and comment.user.user_id is not None
    assert db.session.query(model.User).count() == 1
    assert db.session.query(model.Comment).count() == 1
    assert db.session.query(model.CommentScore).count() == 1

    db.session.delete(comment)
    db.session.commit()

    assert not db.session.dirty
    assert db.session.query(model.User).count() == 1
    assert db.session.query(model.Comment).count() == 0
    assert db.session.query(model.CommentScore).count() == 0
