from datetime import datetime
from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import posts


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {"privileges": {"posts:favorite": model.User.RANK_REGULAR}}
    )


def test_adding_to_favorites(
    user_factory, post_factory, context_factory, fake_datetime
):
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    assert post.score == 0
    with patch("szurubooru.func.posts.serialize_post"), fake_datetime(
        "1997-12-01"
    ):
        posts.serialize_post.return_value = "serialized post"
        result = api.post_api.add_post_to_favorites(
            context_factory(user=user_factory()), {"post_id": post.post_id}
        )
        assert result == "serialized post"
        post = db.session.query(model.Post).one()
        assert db.session.query(model.PostFavorite).count() == 1
        assert post is not None
        assert post.favorite_count == 1
        assert post.score == 1


def test_removing_from_favorites(
    user_factory, post_factory, context_factory, fake_datetime
):
    user = user_factory()
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    assert post.score == 0
    with patch("szurubooru.func.posts.serialize_post"):
        with fake_datetime("1997-12-01"):
            api.post_api.add_post_to_favorites(
                context_factory(user=user), {"post_id": post.post_id}
            )
        assert post.score == 1
        with fake_datetime("1997-12-02"):
            api.post_api.delete_post_from_favorites(
                context_factory(user=user), {"post_id": post.post_id}
            )
        post = db.session.query(model.Post).one()
        assert post.score == 1
        assert db.session.query(model.PostFavorite).count() == 0
        assert post.favorite_count == 0


def test_favoriting_twice(
    user_factory, post_factory, context_factory, fake_datetime
):
    user = user_factory()
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    with patch("szurubooru.func.posts.serialize_post"):
        with fake_datetime("1997-12-01"):
            api.post_api.add_post_to_favorites(
                context_factory(user=user), {"post_id": post.post_id}
            )
        with fake_datetime("1997-12-02"):
            api.post_api.add_post_to_favorites(
                context_factory(user=user), {"post_id": post.post_id}
            )
        post = db.session.query(model.Post).one()
        assert db.session.query(model.PostFavorite).count() == 1
        assert post.favorite_count == 1


def test_removing_twice(
    user_factory, post_factory, context_factory, fake_datetime
):
    user = user_factory()
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    with patch("szurubooru.func.posts.serialize_post"):
        with fake_datetime("1997-12-01"):
            api.post_api.add_post_to_favorites(
                context_factory(user=user), {"post_id": post.post_id}
            )
        with fake_datetime("1997-12-02"):
            api.post_api.delete_post_from_favorites(
                context_factory(user=user), {"post_id": post.post_id}
            )
        with fake_datetime("1997-12-02"):
            api.post_api.delete_post_from_favorites(
                context_factory(user=user), {"post_id": post.post_id}
            )
        post = db.session.query(model.Post).one()
        assert db.session.query(model.PostFavorite).count() == 0
        assert post.favorite_count == 0


def test_favorites_from_multiple_users(
    user_factory, post_factory, context_factory, fake_datetime
):
    user1 = user_factory()
    user2 = user_factory()
    post = post_factory()
    db.session.add_all([user1, user2, post])
    db.session.commit()
    with patch("szurubooru.func.posts.serialize_post"):
        with fake_datetime("1997-12-01"):
            api.post_api.add_post_to_favorites(
                context_factory(user=user1), {"post_id": post.post_id}
            )
        with fake_datetime("1997-12-02"):
            api.post_api.add_post_to_favorites(
                context_factory(user=user2), {"post_id": post.post_id}
            )
        post = db.session.query(model.Post).one()
        assert db.session.query(model.PostFavorite).count() == 2
        assert post.favorite_count == 2
        assert post.last_favorite_time == datetime(1997, 12, 2)


def test_trying_to_update_non_existing(user_factory, context_factory):
    with pytest.raises(posts.PostNotFoundError):
        api.post_api.add_post_to_favorites(
            context_factory(user=user_factory()), {"post_id": 5}
        )


def test_trying_to_rate_without_privileges(
    user_factory, post_factory, context_factory
):
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.post_api.add_post_to_favorites(
            context_factory(user=user_factory(rank=model.User.RANK_ANONYMOUS)),
            {"post_id": post.post_id},
        )
