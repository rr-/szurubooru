from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import posts, snapshots


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "secret": "secret",
            "data_dir": "",
            "delete_source_files": False,
            "privileges": {"posts:delete": model.User.RANK_REGULAR},
        }
    )


def test_deleting(user_factory, post_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    post = post_factory(id=1)
    db.session.add(post)
    db.session.flush()
    with patch("szurubooru.func.snapshots.delete"):
        result = api.post_api.delete_post(
            context_factory(params={"version": 1}, user=auth_user),
            {"post_id": 1},
        )
        assert result == {}
        assert db.session.query(model.Post).count() == 0
        snapshots.delete.assert_called_once_with(post, auth_user)


def test_trying_to_delete_non_existing(user_factory, context_factory):
    with pytest.raises(posts.PostNotFoundError):
        api.post_api.delete_post(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"post_id": 999},
        )


def test_trying_to_delete_without_privileges(
    user_factory, post_factory, context_factory
):
    db.session.add(post_factory(id=1))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.post_api.delete_post(
            context_factory(user=user_factory(rank=model.User.RANK_ANONYMOUS)),
            {"post_id": 1},
        )
    assert db.session.query(model.Post).count() == 1
