from unittest.mock import patch

import pytest

from szurubooru import api, db
from szurubooru.func import user_tokens, users


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"user_tokens:delete:self": "regular"}})


def test_deleting_user_token(
    user_token_factory, context_factory, fake_datetime
):
    user_token = user_token_factory()
    db.session.add(user_token)
    db.session.commit()
    with patch("szurubooru.func.user_tokens.get_by_user_and_token"), patch(
        "szurubooru.func.users.get_user_by_name"
    ), fake_datetime("1969-02-12"):
        users.get_user_by_name.return_value = user_token.user
        user_tokens.get_by_user_and_token.return_value = user_token
        result = api.user_token_api.delete_user_token(
            context_factory(user=user_token.user),
            {
                "user_name": user_token.user.name,
                "user_token": user_token.token,
            },
        )
        assert result == {}
        user_tokens.get_by_user_and_token.assert_called_once_with(
            user_token.user, user_token.token
        )
