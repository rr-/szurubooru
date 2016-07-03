from datetime import datetime
from szurubooru import db

def test_saving_user():
    user = db.User()
    user.name = 'name'
    user.password_salt = 'salt'
    user.password_hash = 'hash'
    user.email = 'email'
    user.rank = 'rank'
    user.creation_time = datetime(1997, 1, 1)
    user.avatar_style = db.User.AVATAR_GRAVATAR
    db.session.add(user)
    db.session.flush()
    db.session.refresh(user)
    assert not db.session.dirty
    assert user.name == 'name'
    assert user.password_salt == 'salt'
    assert user.password_hash == 'hash'
    assert user.email == 'email'
    assert user.rank == 'rank'
    assert user.creation_time == datetime(1997, 1, 1)
    assert user.avatar_style == db.User.AVATAR_GRAVATAR

def test_upload_count(user_factory, post_factory):
    user = user_factory()
    db.session.add(user)
    db.session.flush()
    assert user.post_count == 0
    post1 = post_factory()
    post1.user = user
    post2 = post_factory()
    db.session.add_all([post1, post2])
    db.session.flush()
    db.session.refresh(user)
    assert user.post_count == 1

def test_comment_count(user_factory, comment_factory):
    user = user_factory()
    db.session.add(user)
    db.session.flush()
    assert user.comment_count == 0
    db.session.add_all([
        comment_factory(user=user),
        comment_factory(),
    ])
    db.session.flush()
    db.session.refresh(user)
    assert user.comment_count == 1

def test_favorite_count(user_factory, post_factory):
    user = user_factory()
    db.session.add(user)
    db.session.flush()
    assert user.comment_count == 0
    post1 = post_factory()
    post2 = post_factory()
    db.session.add_all([
        db.PostFavorite(post=post1, time=datetime.utcnow(), user=user),
        db.PostFavorite(post=post2, time=datetime.utcnow(), user=user_factory()),
    ])
    db.session.flush()
    db.session.refresh(user)
    assert user.favorite_post_count == 1

def test_liked_post_count(user_factory, post_factory):
    user = user_factory()
    db.session.add(user)
    db.session.flush()
    assert user.liked_post_count == 0
    assert user.disliked_post_count == 0
    post1 = post_factory()
    post2 = post_factory()
    db.session.add_all([
        db.PostScore(post=post1, time=datetime.utcnow(), user=user, score=1),
        db.PostScore(post=post2, time=datetime.utcnow(), user=user_factory(), score=1),
    ])
    db.session.flush()
    db.session.refresh(user)
    assert user.liked_post_count == 1
    assert user.disliked_post_count == 0

def test_disliked_post_count(user_factory, post_factory):
    user = user_factory()
    db.session.add(user)
    db.session.flush()
    assert user.liked_post_count == 0
    assert user.disliked_post_count == 0
    post1 = post_factory()
    post2 = post_factory()
    db.session.add_all([
        db.PostScore(post=post1, time=datetime.utcnow(), user=user, score=-1),
        db.PostScore(post=post2, time=datetime.utcnow(), user=user_factory(), score=1),
    ])
    db.session.flush()
    db.session.refresh(user)
    assert user.liked_post_count == 0
    assert user.disliked_post_count == 1
