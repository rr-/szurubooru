from unittest.mock import patch

import pytest

from szurubooru import api, errors, model
from szurubooru.func import users


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"users:create:self": "regular"}})


def test_creating_user(user_factory, context_factory, fake_datetime):
    user = user_factory()
    with patch("szurubooru.func.users.create_user"), patch(
        "szurubooru.func.users.update_user_name"
    ), patch("szurubooru.func.users.update_user_password"), patch(
        "szurubooru.func.users.update_user_email"
    ), patch(
        "szurubooru.func.users.update_user_rank"
    ), patch(
        "szurubooru.func.users.update_user_avatar"
    ), patch(
        "szurubooru.func.users.update_user_blocklist"
    ), patch(
        "szurubooru.func.users.serialize_user"
    ), fake_datetime(
        "1969-02-12"
    ):
        users.serialize_user.return_value = "serialized user"
        users.create_user.return_value = user
        users.update_user_blocklist.return_value = ([],[])
        result = api.user_api.create_user(
            context_factory(
                params={
                    "name": "chewie1",
                    "email": "asd@asd.asd",
                    "password": "oks",
                    "rank": "moderator",
                    "avatarStyle": "manual",
                },
                files={"avatar": b"..."},
                user=user_factory(rank=model.User.RANK_REGULAR),
            )
        )
        assert result == "serialized user"
        users.create_user.assert_called_once_with(
            "chewie1", "oks", "asd@asd.asd"
        )
        assert not users.update_user_name.called
        assert not users.update_user_password.called
        assert not users.update_user_email.called
        users.update_user_rank.called_once_with(user, "moderator")
        users.update_user_avatar.called_once_with(user, "manual", b"...")
        users.update_user_blocklist.called_once_with(user, None)


@pytest.mark.parametrize("field", ["name", "password"])
def test_trying_to_omit_mandatory_field(user_factory, context_factory, field):
    params = {
        "name": "chewie",
        "email": "asd@asd.asd",
        "password": "oks",
    }
    user = user_factory()
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    del params[field]
    with patch("szurubooru.func.users.create_user"), pytest.raises(
        errors.MissingRequiredParameterError
    ):
        users.create_user.return_value = user
        api.user_api.create_user(
            context_factory(params=params, user=auth_user)
        )


@pytest.mark.parametrize("field", ["rank", "email", "avatarStyle"])
def test_omitting_optional_field(user_factory, context_factory, field):
    params = {
        "name": "chewie",
        "email": "asd@asd.asd",
        "password": "oks",
        "rank": "moderator",
        "avatarStyle": "gravatar",
    }
    del params[field]
    user = user_factory()
    auth_user = user_factory(rank=model.User.RANK_MODERATOR)
    with patch("szurubooru.func.users.create_user"), patch(
        "szurubooru.func.users.update_user_avatar"
    ), patch("szurubooru.func.users.serialize_user"):
        users.create_user.return_value = user
        api.user_api.create_user(
            context_factory(params=params, user=auth_user)
        )


def test_trying_to_create_user_without_privileges(
    context_factory, user_factory
):
    with pytest.raises(errors.AuthError):
        api.user_api.create_user(
            context_factory(
                params="whatever",
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            )
        )
