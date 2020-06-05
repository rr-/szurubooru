import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import users


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "users:delete:self": model.User.RANK_REGULAR,
                "users:delete:any": model.User.RANK_MODERATOR,
            },
        }
    )


def test_deleting_oneself(user_factory, context_factory):
    user = user_factory(name="u", rank=model.User.RANK_REGULAR)
    db.session.add(user)
    db.session.commit()
    result = api.user_api.delete_user(
        context_factory(params={"version": 1}, user=user), {"user_name": "u"}
    )
    assert result == {}
    assert db.session.query(model.User).count() == 0


def test_deleting_someone_else(user_factory, context_factory):
    user1 = user_factory(name="u1", rank=model.User.RANK_REGULAR)
    user2 = user_factory(name="u2", rank=model.User.RANK_MODERATOR)
    db.session.add_all([user1, user2])
    db.session.commit()
    api.user_api.delete_user(
        context_factory(params={"version": 1}, user=user2), {"user_name": "u1"}
    )
    assert db.session.query(model.User).count() == 1


def test_trying_to_delete_someone_else_without_privileges(
    user_factory, context_factory
):
    user1 = user_factory(name="u1", rank=model.User.RANK_REGULAR)
    user2 = user_factory(name="u2", rank=model.User.RANK_REGULAR)
    db.session.add_all([user1, user2])
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.user_api.delete_user(
            context_factory(params={"version": 1}, user=user2),
            {"user_name": "u1"},
        )
    assert db.session.query(model.User).count() == 2


def test_trying_to_delete_non_existing(user_factory, context_factory):
    with pytest.raises(users.UserNotFoundError):
        api.user_api.delete_user(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"user_name": "bad"},
        )
