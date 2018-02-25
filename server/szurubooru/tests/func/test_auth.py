from szurubooru.func import auth
import pytest


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({'secret': 'testSecret'})


def test_get_password_hash():
    salt, password = ('testSalt', 'pass')
    result = auth.get_password_hash(salt, password)
    assert result
    hash_parts = list(filter(lambda e: e is not None and e != '', result.split('$')))
    assert len(hash_parts) == 5
    assert hash_parts[0] == 'argon2id'


def test_get_sha256_legacy_password_hash():
    salt, password = ('testSalt', 'pass')
    result = auth.get_sha256_legacy_password_hash(salt, password)
    assert result == '2031ac9631353ac9303719a7f808a24f79aa1d71712c98523e4bb4cce579428a'


def test_get_sha1_legacy_password_hash():
    salt, password = ('testSalt', 'pass')
    result = auth.get_sha1_legacy_password_hash(salt, password)
    assert result == '1eb1f953d9be303a1b54627e903e6124cfb1245b'


def test_is_valid_password_auto_upgrades_user_password_hash_on_success_of_legacy_hash(user_factory):
    salt, password = ('testSalt', 'pass')
    legacy_password_hash = auth.get_sha256_legacy_password_hash(salt, password)
    user = user_factory(password_salt=salt, password_hash=legacy_password_hash)
    result = auth.is_valid_password(user, password)
    assert result is True
    assert user.password_hash != legacy_password_hash
