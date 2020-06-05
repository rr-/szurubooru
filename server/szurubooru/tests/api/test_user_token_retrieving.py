from unittest.mock import patch

import pytest

from szurubooru import api
from szurubooru.func import user_tokens, users


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"user_tokens:list:self": "regular"}})


def test_retrieving_user_tokens(
    user_token_factory, context_factory, fake_datetime
):
    user_token1 = user_token_factory()
    user_token2 = user_token_factory(user=user_token1.user)
    user_token3 = user_token_factory(user=user_token1.user)
    with patch("szurubooru.func.user_tokens.get_user_tokens"), patch(
        "szurubooru.func.user_tokens.serialize_user_token"
    ), patch("szurubooru.func.users.get_user_by_name"), fake_datetime(
        "1969-02-12"
    ):
        users.get_user_by_name.return_value = user_token1.user
        user_tokens.serialize_user_token.return_value = "serialized user token"
        user_tokens.get_user_tokens.return_value = [
            user_token1,
            user_token2,
            user_token3,
        ]
        result = api.user_token_api.get_user_tokens(
            context_factory(user=user_token1.user),
            {"user_name": user_token1.user.name},
        )
        assert result == {"results": ["serialized user token"] * 3}
        user_tokens.get_user_tokens.assert_called_once_with(user_token1.user)
