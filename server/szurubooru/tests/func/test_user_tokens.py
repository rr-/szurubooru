from datetime import datetime
from unittest.mock import patch

from szurubooru import db
from szurubooru.func import user_tokens, users, auth


def test_serialize_user_token(user_token_factory):
    user_token = user_token_factory()
    db.session.add(user_token)
    db.session.flush()
    with patch('szurubooru.func.users.get_avatar_url'):
        users.get_avatar_url.return_value = 'https://example.com/avatar.png'
        result = user_tokens.serialize_user_token(user_token, user_token.user)
        assert result == {'creationTime': datetime(1997, 1, 1, 0, 0),
                          'enabled': True,
                          'lastEditTime': None,
                          'token': 'dummy',
                          'user': {
                              'avatarUrl': 'https://example.com/avatar.png',
                              'name': user_token.user.name},
                          'version': 1}


def test_serialize_user_token_none():
    result = user_tokens.serialize_user_token(None, None)
    assert result is None


def test_get_user_token_by_user_and_token(user_token_factory):
    user_token = user_token_factory()
    db.session.add(user_token)
    db.session.flush()
    db.session.commit()
    result = user_tokens.get_user_token_by_user_and_token(user_token.user, user_token.token)
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
    with patch('szurubooru.func.auth.generate_authorization_token'):
        auth.generate_authorization_token.return_value = 'test'
        result = user_tokens.create_user_token(user)
        assert result.token == 'test'
        assert result.user == user


def test_update_user_token_enabled(user_token_factory):
    user_token = user_token_factory()
    user_tokens.update_user_token_enabled(user_token, False)
    assert user_token.enabled is False


def test_update_user_token_edit_time(user_token_factory):
    user_token = user_token_factory()
    assert user_token.last_edit_time is None
    user_tokens.update_user_token_edit_time(user_token)
    assert user_token.last_edit_time is not None
