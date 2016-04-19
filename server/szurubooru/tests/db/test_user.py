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
