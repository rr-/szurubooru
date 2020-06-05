import random
import string
from datetime import datetime, timedelta
from unittest.mock import patch

import pytest
import pytz

from szurubooru import db, model
from szurubooru.func import auth, user_tokens, users, util


def test_serialize_user_token(user_token_factory):
    user_token = user_token_factory()
    db.session.add(user_token)
    db.session.flush()
    with patch("szurubooru.func.users.get_avatar_url"):
        users.get_avatar_url.return_value = "https://example.com/avatar.png"
        result = user_tokens.serialize_user_token(user_token, user_token.user)
        assert result == {
            "creationTime": datetime(1997, 1, 1, 0, 0),
            "enabled": True,
            "expirationTime": None,
            "lastEditTime": None,
            "lastUsageTime": None,
            "note": None,
            "token": "dummy",
            "user": {
                "avatarUrl": "https://example.com/avatar.png",
                "name": user_token.user.name,
            },
            "version": 1,
        }


def test_serialize_user_token_none():
    result = user_tokens.serialize_user_token(None, None)
    assert result is None


def test_get_by_user_and_token(user_token_factory):
    user_token = user_token_factory()
    db.session.add(user_token)
    db.session.flush()
    db.session.commit()
    result = user_tokens.get_by_user_and_token(
        user_token.user, user_token.token
    )
    assert result == user_token


def test_get_user_tokens(user_token_factory):
    user_token1 = user_token_factory()
    user_token2 = user_token_factory(user=user_token1.user)
    db.session.add(user_token1)
    db.session.add(user_token2)
    db.session.flush()
    db.session.commit()
    result = user_tokens.get_user_tokens(user_token1.user)
    assert result == [user_token1, user_token2]


def test_create_user_token(user_factory):
    user = user_factory()
    db.session.add(user)
    db.session.flush()
    db.session.commit()
    with patch("szurubooru.func.auth.generate_authorization_token"):
        auth.generate_authorization_token.return_value = "test"
        result = user_tokens.create_user_token(user, True)
        assert result.token == "test"
        assert result.user == user


def test_update_user_token_enabled(user_token_factory):
    user_token = user_token_factory()
    user_tokens.update_user_token_enabled(user_token, False)
    assert user_token.enabled is False
    assert user_token.last_edit_time is not None


def test_update_user_token_edit_time(user_token_factory):
    user_token = user_token_factory()
    assert user_token.last_edit_time is None
    user_tokens.update_user_token_edit_time(user_token)
    assert user_token.last_edit_time is not None


def test_update_user_token_note(user_token_factory):
    user_token = user_token_factory()
    assert user_token.note is None
    user_tokens.update_user_token_note(user_token, " Test Note ")
    assert user_token.note == "Test Note"
    assert user_token.last_edit_time is not None


def test_update_user_token_note_input_too_long(user_token_factory):
    user_token = user_token_factory()
    assert user_token.note is None
    note_max_length = util.get_column_size(model.UserToken.note) + 1
    note = "".join(
        random.choice(string.ascii_letters) for _ in range(note_max_length)
    )
    with pytest.raises(user_tokens.InvalidNoteError):
        user_tokens.update_user_token_note(user_token, note)


def test_update_user_token_expiration_time(user_token_factory):
    user_token = user_token_factory()
    assert user_token.expiration_time is None
    expiration_time_str = (
        (datetime.utcnow() + timedelta(days=1)).replace(tzinfo=pytz.utc)
    ).isoformat()
    user_tokens.update_user_token_expiration_time(
        user_token, expiration_time_str
    )
    assert user_token.expiration_time.isoformat() == expiration_time_str
    assert user_token.last_edit_time is not None


def test_update_user_token_expiration_time_in_past(user_token_factory):
    user_token = user_token_factory()
    assert user_token.expiration_time is None
    expiration_time_str = (
        (datetime.utcnow() - timedelta(days=1)).replace(tzinfo=pytz.utc)
    ).isoformat()
    with pytest.raises(
        user_tokens.InvalidExpirationError,
        match="Expiration cannot happen in the past",
    ):
        user_tokens.update_user_token_expiration_time(
            user_token, expiration_time_str
        )


@pytest.mark.parametrize(
    "expiration_time_str",
    [
        datetime.utcnow().isoformat(),
        (datetime.utcnow() - timedelta(days=1)).ctime(),
        "1970/01/01 00:00:01.0000Z",
        "70/01/01 00:00:01.0000Z",
        "".join(random.choice(string.ascii_letters) for _ in range(15)),
        "".join(random.choice(string.digits) for _ in range(8)),
    ],
)
def test_update_user_token_expiration_time_invalid_format(
    expiration_time_str, user_token_factory
):
    user_token = user_token_factory()
    assert user_token.expiration_time is None

    with pytest.raises(
        user_tokens.InvalidExpirationError,
        match="Expiration is in an invalid format %s" % expiration_time_str,
    ):
        user_tokens.update_user_token_expiration_time(
            user_token, expiration_time_str
        )


def test_bump_usage_time(user_token_factory, fake_datetime):
    user_token = user_token_factory()
    with fake_datetime("1997-01-01"):
        user_tokens.bump_usage_time(user_token)
        assert user_token.last_usage_time == datetime(1997, 1, 1)
