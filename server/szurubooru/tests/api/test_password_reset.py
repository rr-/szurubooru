from datetime import datetime
from unittest import mock
import pytest
from szurubooru import api, db, errors
from szurubooru.util import auth, mailer

def mock_user(name, rank, email):
    user = db.User()
    user.name = name
    user.password = 'dummy'
    user.password_salt = 'dummy'
    user.password_hash = 'dummy'
    user.email = email
    user.rank = rank
    user.creation_time = datetime(1997, 1, 1)
    user.avatar_style = db.User.AVATAR_GRAVATAR
    return user

@pytest.fixture
def password_reset_api(config_injector):
    config_injector({
        'secret': 'x',
        'base_url': 'http://example.com/',
        'name': 'Test instance',
    })
    return api.PasswordResetApi()

def test_reset_non_existing(password_reset_api, context_factory):
    with pytest.raises(errors.NotFoundError):
        password_reset_api.get(context_factory(), 'u1')

def test_reset_without_email(password_reset_api, session, context_factory):
    user = mock_user('u1', 'regular_user', None)
    session.add(user)
    with pytest.raises(errors.ValidationError):
        password_reset_api.get(context_factory(), 'u1')

def test_reset_sending_email(password_reset_api, session, context_factory):
    user = mock_user('u1', 'regular_user', 'user@example.com')
    session.add(user)
    for getter in ['u1', 'user@example.com']:
        mailer.send_mail = mock.MagicMock()
        assert password_reset_api.get(context_factory(), getter) == {}
        mailer.send_mail.assert_called_once_with(
            'noreply@Test instance',
            'user@example.com',
            'Password reset for Test instance',
            'You (or someone else) requested to reset your password ' +
            'on Test instance.\nIf you wish to proceed, click this l' +
            'ink: http://example.com/password-reset/u1:4ac0be176fb36' +
            '4f13ee6b634c43220e2\nOtherwise, please ignore this email.')

def test_confirmation_non_existing(password_reset_api, context_factory):
    with pytest.raises(errors.NotFoundError):
        password_reset_api.post(context_factory(), 'u1')

def test_confirmation_no_token(password_reset_api, context_factory, session):
    user = mock_user('u1', 'regular_user', 'user@example.com')
    session.add(user)
    with pytest.raises(errors.ValidationError):
        password_reset_api.post(context_factory(request={}), 'u1')

def test_confirmation_bad_token(password_reset_api, context_factory, session):
    user = mock_user('u1', 'regular_user', 'user@example.com')
    session.add(user)
    with pytest.raises(errors.ValidationError):
        password_reset_api.post(
            context_factory(request={'token': 'bad'}), 'u1')

def test_confirmation_good_token(password_reset_api, context_factory, session):
    user = mock_user('u1', 'regular_user', 'user@example.com')
    old_hash = user.password_hash
    session.add(user)
    context = context_factory(
        request={'token': '4ac0be176fb364f13ee6b634c43220e2'})
    result = password_reset_api.post(context, 'u1')
    assert user.password_hash != old_hash
    assert auth.is_valid_password(user, result['password']) is True
