from datetime import datetime

from szurubooru import db


def test_saving_user_token(user_token_factory):
    user_token = user_token_factory()
    db.session.add(user_token)
    db.session.flush()
    db.session.refresh(user_token)
    assert not db.session.dirty
    assert user_token.user is not None
    assert user_token.token == "dummy"
    assert user_token.enabled is True
    assert user_token.creation_time == datetime(1997, 1, 1)
