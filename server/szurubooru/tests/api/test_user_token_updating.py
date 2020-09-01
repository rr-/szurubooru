from unittest.mock import patch

import pytest

from szurubooru import api, db
from szurubooru.func import user_tokens, users


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"user_tokens:edit:self": "regular"}})


def test_edit_user_token(user_token_factory, context_factory, fake_datetime):
    user_token = user_token_factory()
    db.session.add(user_token)
    db.session.commit()
    with patch("szurubooru.func.user_tokens.get_by_user_and_token"), patch(
        "szurubooru.func.user_tokens.update_user_token_enabled"
    ), patch("szurubooru.func.user_tokens.update_user_token_edit_time"), patch(
        "szurubooru.func.user_tokens.serialize_user_token"
    ), patch(
        "szurubooru.func.users.get_user_by_name"
    ), fake_datetime(
        "1969-02-12"
    ):
        users.get_user_by_name.return_value = user_token.user
        user_tokens.serialize_user_token.return_value = "serialized user token"
        user_tokens.get_by_user_and_token.return_value = user_token
        result = api.user_token_api.update_user_token(
            context_factory(
                params={
                    "version": user_token.version,
                    "enabled": False,
                },
                user=user_token.user,
            ),
            {
                "user_name": user_token.user.name,
                "user_token": user_token.token,
            },
        )
        assert result == "serialized user token"
        user_tokens.get_by_user_and_token.assert_called_once_with(
            user_token.user, user_token.token
        )
        user_tokens.update_user_token_enabled.assert_called_once_with(
            user_token, False
        )
        user_tokens.update_user_token_edit_time.assert_called_once_with(
            user_token
        )
