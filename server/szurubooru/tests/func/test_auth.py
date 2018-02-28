from szurubooru.func import auth
import pytest


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({'secret': 'testSecret'})


def test_get_sha256_legacy_password_hash():
    salt, password = ('testSalt', 'pass')
    result = auth.get_sha256_legacy_password_hash(salt, password)
    assert result == '2031ac9631353ac9303719a7f808a24f79aa1d71712c98523e4bb4cce579428a'


def test_get_sha1_legacy_password_hash():
    salt, password = ('testSalt', 'pass')
    result = auth.get_sha1_legacy_password_hash(salt, password)
    assert result == '1eb1f953d9be303a1b54627e903e6124cfb1245b'


def test_is_valid_password(user_factory):
    salt, password = ('testSalt', 'pass')
    user = user_factory(password_salt=salt, password=password)
    legacy_password_hash = auth.get_sha256_legacy_password_hash(salt, password)
    user.password_hash = legacy_password_hash
    result = auth.is_valid_password(user, password)
    assert result is True
    assert user.password_hash != legacy_password_hash


def test_is_valid_token(user_token_factory):
    user_token = user_token_factory()
    assert auth.is_valid_token(user_token)


def test_generate_authorization_token():
    result = auth.generate_authorization_token()
    assert result != auth.generate_authorization_token()
