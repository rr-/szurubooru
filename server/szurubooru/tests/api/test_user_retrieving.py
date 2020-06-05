from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import users


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "users:list": model.User.RANK_REGULAR,
                "users:view": model.User.RANK_REGULAR,
                "users:edit:any:email": model.User.RANK_MODERATOR,
            },
        }
    )


def test_retrieving_multiple(user_factory, context_factory):
    user1 = user_factory(name="u1", rank=model.User.RANK_MODERATOR)
    user2 = user_factory(name="u2", rank=model.User.RANK_MODERATOR)
    db.session.add_all([user1, user2])
    db.session.flush()
    with patch("szurubooru.func.users.serialize_user"):
        users.serialize_user.return_value = "serialized user"
        result = api.user_api.get_users(
            context_factory(
                params={"query": "", "page": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            )
        )
        assert result == {
            "query": "",
            "offset": 0,
            "limit": 100,
            "total": 2,
            "results": ["serialized user", "serialized user"],
        }


def test_trying_to_retrieve_multiple_without_privileges(
    user_factory, context_factory
):
    with pytest.raises(errors.AuthError):
        api.user_api.get_users(
            context_factory(
                params={"query": "", "page": 1},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            )
        )


def test_retrieving_single(user_factory, context_factory):
    user = user_factory(name="u1", rank=model.User.RANK_REGULAR)
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    db.session.add(user)
    db.session.flush()
    with patch("szurubooru.func.users.serialize_user"):
        users.serialize_user.return_value = "serialized user"
        result = api.user_api.get_user(
            context_factory(user=auth_user), {"user_name": "u1"}
        )
        assert result == "serialized user"


def test_trying_to_retrieve_single_non_existing(user_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    with pytest.raises(users.UserNotFoundError):
        api.user_api.get_user(
            context_factory(user=auth_user), {"user_name": "-"}
        )


def test_trying_to_retrieve_single_without_privileges(
    user_factory, context_factory
):
    auth_user = user_factory(rank=model.User.RANK_ANONYMOUS)
    db.session.add(user_factory(name="u1", rank=model.User.RANK_REGULAR))
    db.session.flush()
    with pytest.raises(errors.AuthError):
        api.user_api.get_user(
            context_factory(user=auth_user), {"user_name": "u1"}
        )
