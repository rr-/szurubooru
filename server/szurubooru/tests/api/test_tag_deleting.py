from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import snapshots, tags


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"tags:delete": model.User.RANK_REGULAR}})


def test_deleting(user_factory, tag_factory, context_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    tag = tag_factory(names=["tag"])
    db.session.add(tag)
    db.session.commit()
    with patch("szurubooru.func.snapshots.delete"):
        result = api.tag_api.delete_tag(
            context_factory(params={"version": 1}, user=auth_user),
            {"tag_name": "tag"},
        )
        assert result == {}
        assert db.session.query(model.Tag).count() == 0
        snapshots.delete.assert_called_once_with(tag, auth_user)


def test_deleting_used(
    user_factory, tag_factory, context_factory, post_factory
):
    tag = tag_factory(names=["tag"])
    post = post_factory()
    post.tags.append(tag)
    db.session.add_all([tag, post])
    db.session.commit()
    with patch("szurubooru.func.snapshots._post_to_webhooks"):
        api.tag_api.delete_tag(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"tag_name": "tag"},
        )
        db.session.refresh(post)
        assert db.session.query(model.Tag).count() == 0
        assert post.tags == []


def test_trying_to_delete_non_existing(user_factory, context_factory):
    with pytest.raises(tags.TagNotFoundError):
        api.tag_api.delete_tag(
            context_factory(user=user_factory(rank=model.User.RANK_REGULAR)),
            {"tag_name": "bad"},
        )


def test_trying_to_delete_without_privileges(
    user_factory, tag_factory, context_factory
):
    db.session.add(tag_factory(names=["tag"]))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.tag_api.delete_tag(
            context_factory(
                params={"version": 1},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"tag_name": "tag"},
        )
    assert db.session.query(model.Tag).count() == 1
