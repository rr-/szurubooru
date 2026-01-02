from datetime import datetime
from unittest.mock import patch

import pytest

from szurubooru import db, errors, model
from szurubooru.func import auth, files, users, util

EMPTY_PIXEL = (
    b"\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x01\x00\x00\x00\x00"
    b"\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x01\x00\x2c\x00\x00\x00\x00"
    b"\x01\x00\x01\x00\x00\x02\x02\x4c\x01\x00\x3b"
)


@pytest.mark.parametrize("user_name", ["test", "TEST"])
def test_get_avatar_path(user_name):
    assert users.get_avatar_path(user_name) == "avatars/test.png"


@pytest.mark.parametrize(
    "user_name,user_email,avatar_style,expected_url",
    [
        (
            "user",
            None,
            model.User.AVATAR_GRAVATAR,
            (
                "https://gravatar.com/avatar/"
                + "ee11cbb19052e40b07aac0ca060c23ee?d=retro&s=100"
            ),
        ),
        (
            None,
            "user@example.com",
            model.User.AVATAR_GRAVATAR,
            (
                "https://gravatar.com/avatar/"
                + "b58996c504c5638798eb6b511e6f49af?d=retro&s=100"
            ),
        ),
        (
            "user",
            "user@example.com",
            model.User.AVATAR_GRAVATAR,
            (
                "https://gravatar.com/avatar/"
                + "b58996c504c5638798eb6b511e6f49af?d=retro&s=100"
            ),
        ),
        (
            "user",
            None,
            model.User.AVATAR_MANUAL,
            "http://example.com/avatars/user.png",
        ),
    ],
)
def test_get_avatar_url(
    user_name, user_email, avatar_style, expected_url, config_injector
):
    config_injector(
        {
            "data_url": "http://example.com/",
            "thumbnails": {"avatar_width": 100},
        }
    )
    user = model.User()
    user.name = user_name
    user.email = user_email
    user.avatar_style = avatar_style
    assert users.get_avatar_url(user) == expected_url


@pytest.mark.parametrize(
    "same_user,can_edit_any_email,force_show,expected_email",
    [
        (False, False, False, False),
        (True, False, False, "test@example.com"),
        (False, True, False, "test@example.com"),
        (False, False, True, "test@example.com"),
    ],
)
def test_get_email(
    same_user, can_edit_any_email, force_show, expected_email, user_factory
):
    with patch("szurubooru.func.auth.has_privilege"):
        auth.has_privilege = lambda user, name: can_edit_any_email
        user = user_factory()
        user.email = "test@example.com"
        auth_user = user if same_user else user_factory()
        db.session.add_all([user, auth_user])
        db.session.flush()
        assert users.get_email(user, auth_user, force_show) == expected_email


@pytest.mark.parametrize(
    "same_user,score,expected_liked_post_count,expected_disliked_post_count",
    [
        (False, 1, False, False),
        (False, -1, False, False),
        (True, 1, 1, 0),
        (True, -1, 0, 1),
    ],
)
def test_get_liked_post_count(
    same_user,
    score,
    expected_liked_post_count,
    expected_disliked_post_count,
    user_factory,
    post_factory,
):
    user = user_factory()
    post = post_factory()
    auth_user = user if same_user else user_factory()
    score = model.PostScore(
        post=post, user=user, score=score, time=datetime.now()
    )
    db.session.add_all([post, user, score])
    db.session.flush()
    actual_liked_post_count = users.get_liked_post_count(user, auth_user)
    actual_disliked_post_count = users.get_disliked_post_count(user, auth_user)
    assert actual_liked_post_count == expected_liked_post_count
    assert actual_disliked_post_count == expected_disliked_post_count


def test_serialize_user_when_empty():
    assert users.serialize_user(None, None) is None


def test_serialize_user(user_factory):
    with patch("szurubooru.func.users.get_email"), patch(
        "szurubooru.func.users.get_avatar_url"
    ), patch("szurubooru.func.users.get_liked_post_count"), patch(
        "szurubooru.func.users.get_disliked_post_count"
    ):
        users.get_email.return_value = "test@example.com"
        users.get_avatar_url.return_value = "https://example.com/avatar.png"
        users.get_liked_post_count.return_value = 66
        users.get_disliked_post_count.return_value = 33
        auth_user = user_factory()
        user = user_factory(name="dummy user")
        user.creation_time = datetime(1997, 1, 1)
        user.last_edit_time = datetime(1998, 1, 1)
        user.avatar_style = model.User.AVATAR_MANUAL
        user.rank = model.User.RANK_ADMINISTRATOR
        db.session.add(user)
        db.session.flush()
        assert users.serialize_user(user, auth_user) == {
            "version": 1,
            "name": "dummy user",
            "email": "test@example.com",
            "rank": "administrator",
            "creationTime": datetime(1997, 1, 1, 0, 0),
            "lastLoginTime": None,
            "avatarStyle": "manual",
            "avatarUrl": "https://example.com/avatar.png",
            "likedPostCount": 66,
            "dislikedPostCount": 33,
            "blocklist": [],
            "commentCount": 0,
            "favoritePostCount": 0,
            "uploadedPostCount": 0,
        }


def test_serialize_micro_user(user_factory):
    with patch("szurubooru.func.users.get_avatar_url"):
        users.get_avatar_url.return_value = "https://example.com/avatar.png"
        auth_user = user_factory()
        user = user_factory(name="dummy user")
        db.session.add(user)
        db.session.flush()
        assert users.serialize_micro_user(user, auth_user) == {
            "name": "dummy user",
            "avatarUrl": "https://example.com/avatar.png",
        }


@pytest.mark.parametrize("count", [0, 1, 2])
def test_get_user_count(count, user_factory):
    for _ in range(count):
        db.session.add(user_factory())
    db.session.flush()
    assert users.get_user_count() == count


def test_try_get_user_by_name(user_factory):
    user = user_factory(name="name", email="email")
    db.session.add(user)
    db.session.flush()
    assert users.try_get_user_by_name("non-existing") is None
    assert users.try_get_user_by_name("email") is None
    assert users.try_get_user_by_name("name") is user
    assert users.try_get_user_by_name("NAME") is user


def test_get_user_by_name(user_factory):
    user = user_factory(name="name", email="email")
    db.session.add(user)
    db.session.flush()
    with pytest.raises(users.UserNotFoundError):
        assert users.get_user_by_name("non-existing")
    with pytest.raises(users.UserNotFoundError):
        assert users.get_user_by_name("email")
    assert users.get_user_by_name("name") is user
    assert users.get_user_by_name("NAME") is user


def test_try_get_user_by_name_or_email(user_factory):
    user = user_factory(name="name", email="email")
    db.session.add(user)
    db.session.flush()
    assert users.try_get_user_by_name_or_email("non-existing") is None
    assert users.try_get_user_by_name_or_email("email") is user
    assert users.try_get_user_by_name_or_email("EMAIL") is user
    assert users.try_get_user_by_name_or_email("name") is user
    assert users.try_get_user_by_name_or_email("NAME") is user


def test_get_user_by_name_or_email(user_factory):
    user = user_factory(name="name", email="email")
    db.session.add(user)
    db.session.flush()
    with pytest.raises(users.UserNotFoundError):
        assert users.get_user_by_name_or_email("non-existing")
    assert users.get_user_by_name_or_email("email") is user
    assert users.get_user_by_name_or_email("EMAIL") is user
    assert users.get_user_by_name_or_email("name") is user
    assert users.get_user_by_name_or_email("NAME") is user


def test_create_user_for_first_user(fake_datetime):
    with patch("szurubooru.func.users.update_user_name"), patch(
        "szurubooru.func.users.update_user_password"
    ), patch("szurubooru.func.users.update_user_email"), fake_datetime(
        "1997-01-01"
    ), patch("szurubooru.func.users.update_user_blocklist"):
        user = users.create_user("name", "password", "email")
        assert user.creation_time == datetime(1997, 1, 1)
        assert user.last_login_time is None
        assert user.rank == model.User.RANK_ADMINISTRATOR
        users.update_user_name.assert_called_once_with(user, "name")
        users.update_user_password.assert_called_once_with(user, "password")
        users.update_user_email.assert_called_once_with(user, "email")


def test_create_user_for_subsequent_users(user_factory, config_injector):
    config_injector({"default_rank": "regular"})
    db.session.add(user_factory())
    db.session.flush()
    with patch("szurubooru.func.users.update_user_name"), patch(
        "szurubooru.func.users.update_user_email"
    ), patch("szurubooru.func.users.update_user_password"
    ), patch("szurubooru.func.users.update_user_blocklist"):
        user = users.create_user("name", "password", "email")
        assert user.rank == model.User.RANK_REGULAR


def test_update_user_name_with_empty_string(user_factory):
    user = user_factory()
    with pytest.raises(users.InvalidUserNameError):
        users.update_user_name(user, None)


def test_update_user_name_with_too_long_string(user_factory):
    user = user_factory()
    with pytest.raises(users.InvalidUserNameError):
        users.update_user_name(user, "a" * 300)


def test_update_user_name_with_invalid_name(user_factory, config_injector):
    config_injector({"user_name_regex": "^[a-z]+$"})
    user = user_factory()
    with pytest.raises(users.InvalidUserNameError):
        users.update_user_name(user, "0")


def test_update_user_name_with_duplicate_name(user_factory, config_injector):
    config_injector({"user_name_regex": "^[a-z]+$"})
    user = user_factory()
    existing_user = user_factory(name="dummy")
    db.session.add(existing_user)
    db.session.flush()
    with pytest.raises(users.UserAlreadyExistsError):
        users.update_user_name(user, "dummy")


def test_update_user_name_reusing_own_name(user_factory, config_injector):
    config_injector({"user_name_regex": "^[a-z]+$"})
    user = user_factory(name="dummy")
    db.session.add(user)
    db.session.flush()
    with patch("szurubooru.func.files.has"):
        files.has.return_value = False
        users.update_user_name(user, "dummy")
        db.session.flush()
        assert users.try_get_user_by_name("dummy") is user


def test_update_user_name_for_new_user(user_factory, config_injector):
    config_injector({"user_name_regex": "^[a-z]+$"})
    user = user_factory()
    with patch("szurubooru.func.files.has"):
        files.has.return_value = False
        users.update_user_name(user, "dummy")
        assert user.name == "dummy"


def test_update_user_name_moves_avatar(user_factory, config_injector):
    config_injector({"user_name_regex": "^[a-z]+$"})
    user = user_factory(name="old")
    with patch("szurubooru.func.files.has"), patch(
        "szurubooru.func.files.move"
    ):
        files.has.return_value = True
        users.update_user_name(user, "new")
        files.move.assert_called_once_with(
            "avatars/old.png", "avatars/new.png"
        )


def test_update_user_password_with_empty_string(user_factory):
    user = user_factory()
    with pytest.raises(users.InvalidPasswordError):
        users.update_user_password(user, None)


def test_update_user_password_with_invalid_string(
    user_factory, config_injector
):
    config_injector({"password_regex": "^[a-z]+$"})
    user = user_factory()
    with pytest.raises(users.InvalidPasswordError):
        users.update_user_password(user, "0")


def test_update_user_password(user_factory, config_injector):
    config_injector({"password_regex": "^[a-z]+$"})
    user = user_factory()
    with patch("szurubooru.func.auth.create_password"), patch(
        "szurubooru.func.auth.get_password_hash"
    ):
        auth.create_password.return_value = "salt"
        auth.get_password_hash.return_value = ("hash", 3)
        users.update_user_password(user, "a")
        assert user.password_salt == "salt"
        assert user.password_hash == "hash"
        assert user.password_revision == 3


def test_update_user_email_with_too_long_string(user_factory):
    user = user_factory()
    with pytest.raises(users.InvalidEmailError):
        users.update_user_email(user, "a" * 300)


def test_update_user_email_with_invalid_email(user_factory):
    user = user_factory()
    with patch("szurubooru.func.util.is_valid_email"):
        util.is_valid_email.return_value = False
        with pytest.raises(users.InvalidEmailError):
            users.update_user_email(user, "a")


def test_update_user_email_with_empty_string(user_factory):
    user = user_factory()
    with patch("szurubooru.func.util.is_valid_email"):
        util.is_valid_email.return_value = True
        users.update_user_email(user, "")
        assert user.email is None


def test_update_user_email(user_factory):
    user = user_factory()
    with patch("szurubooru.func.util.is_valid_email"):
        util.is_valid_email.return_value = True
        users.update_user_email(user, "a")
        assert user.email == "a"


def test_update_user_rank_with_empty_string(user_factory):
    user = user_factory()
    auth_user = user_factory()
    with pytest.raises(users.InvalidRankError):
        users.update_user_rank(user, "", auth_user)


def test_update_user_rank_with_invalid_string(user_factory):
    user = user_factory()
    auth_user = user_factory()
    with pytest.raises(users.InvalidRankError):
        users.update_user_rank(user, "invalid", auth_user)
    with pytest.raises(users.InvalidRankError):
        users.update_user_rank(user, "anonymous", auth_user)
    with pytest.raises(users.InvalidRankError):
        users.update_user_rank(user, "nobody", auth_user)


def test_update_user_rank_with_higher_rank_than_possible(user_factory):
    db.session.add(user_factory())
    db.session.flush()
    user = user_factory()
    auth_user = user_factory()
    auth_user.rank = model.User.RANK_ANONYMOUS
    with pytest.raises(errors.AuthError):
        users.update_user_rank(user, "regular", auth_user)
    with pytest.raises(errors.AuthError):
        users.update_user_rank(auth_user, "regular", auth_user)


def test_update_user_rank(user_factory):
    db.session.add(user_factory())
    db.session.flush()
    user = user_factory()
    auth_user = user_factory()
    auth_user.rank = model.User.RANK_ADMINISTRATOR
    users.update_user_rank(user, "regular", auth_user)
    users.update_user_rank(auth_user, "regular", auth_user)
    assert user.rank == model.User.RANK_REGULAR
    assert auth_user.rank == model.User.RANK_REGULAR


def test_update_user_avatar_with_invalid_style(user_factory):
    user = user_factory()
    with pytest.raises(users.InvalidAvatarError):
        users.update_user_avatar(user, "invalid", b"")


def test_update_user_avatar_to_gravatar(user_factory):
    user = user_factory()
    users.update_user_avatar(user, "gravatar")
    assert user.avatar_style == model.User.AVATAR_GRAVATAR


def test_update_user_avatar_to_empty_manual(user_factory):
    user = user_factory()
    with patch("szurubooru.func.files.has"), pytest.raises(
        users.InvalidAvatarError
    ):
        files.has.return_value = False
        users.update_user_avatar(user, "manual", b"")


def test_update_user_avatar_to_previous_manual(user_factory):
    user = user_factory()
    with patch("szurubooru.func.files.has"):
        files.has.return_value = True
        users.update_user_avatar(user, "manual", b"")


def test_update_user_avatar_to_new_manual(user_factory, config_injector):
    config_injector(
        {"thumbnails": {"avatar_width": 500, "avatar_height": 500}}
    )
    user = user_factory()
    with patch("szurubooru.func.files.save"):
        users.update_user_avatar(user, "manual", EMPTY_PIXEL)
        assert user.avatar_style == model.User.AVATAR_MANUAL
        assert files.save.called


def test_bump_user_login_time(user_factory, fake_datetime):
    user = user_factory()
    with fake_datetime("1997-01-01"):
        users.bump_user_login_time(user)
        assert user.last_login_time == datetime(1997, 1, 1)


def test_reset_user_password(user_factory):
    with patch("szurubooru.func.auth.create_password"), patch(
        "szurubooru.func.auth.get_password_hash"
    ):
        user = user_factory()
        auth.create_password.return_value = "salt"
        auth.get_password_hash.return_value = ("hash", 3)
        users.reset_user_password(user)
        assert user.password_salt == "salt"
        assert user.password_hash == "hash"
        assert user.password_revision == 3
