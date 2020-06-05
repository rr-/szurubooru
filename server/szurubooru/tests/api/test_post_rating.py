from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import posts


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"posts:score": model.User.RANK_REGULAR}})


def test_simple_rating(
    user_factory, post_factory, context_factory, fake_datetime
):
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    with patch("szurubooru.func.posts.serialize_post"), fake_datetime(
        "1997-12-01"
    ):
        posts.serialize_post.return_value = "serialized post"
        result = api.post_api.set_post_score(
            context_factory(params={"score": 1}, user=user_factory()),
            {"post_id": post.post_id},
        )
        assert result == "serialized post"
        post = db.session.query(model.Post).one()
        assert db.session.query(model.PostScore).count() == 1
        assert post is not None
        assert post.score == 1


def test_updating_rating(
    user_factory, post_factory, context_factory, fake_datetime
):
    user = user_factory()
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    with patch("szurubooru.func.posts.serialize_post"):
        with fake_datetime("1997-12-01"):
            api.post_api.set_post_score(
                context_factory(params={"score": 1}, user=user),
                {"post_id": post.post_id},
            )
        with fake_datetime("1997-12-02"):
            api.post_api.set_post_score(
                context_factory(params={"score": -1}, user=user),
                {"post_id": post.post_id},
            )
        post = db.session.query(model.Post).one()
        assert db.session.query(model.PostScore).count() == 1
        assert post.score == -1


def test_updating_rating_to_zero(
    user_factory, post_factory, context_factory, fake_datetime
):
    user = user_factory()
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    with patch("szurubooru.func.posts.serialize_post"):
        with fake_datetime("1997-12-01"):
            api.post_api.set_post_score(
                context_factory(params={"score": 1}, user=user),
                {"post_id": post.post_id},
            )
        with fake_datetime("1997-12-02"):
            api.post_api.set_post_score(
                context_factory(params={"score": 0}, user=user),
                {"post_id": post.post_id},
            )
        post = db.session.query(model.Post).one()
        assert db.session.query(model.PostScore).count() == 0
        assert post.score == 0


def test_deleting_rating(
    user_factory, post_factory, context_factory, fake_datetime
):
    user = user_factory()
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    with patch("szurubooru.func.posts.serialize_post"):
        with fake_datetime("1997-12-01"):
            api.post_api.set_post_score(
                context_factory(params={"score": 1}, user=user),
                {"post_id": post.post_id},
            )
        with fake_datetime("1997-12-02"):
            api.post_api.delete_post_score(
                context_factory(user=user), {"post_id": post.post_id}
            )
        post = db.session.query(model.Post).one()
        assert db.session.query(model.PostScore).count() == 0
        assert post.score == 0


def test_ratings_from_multiple_users(
    user_factory, post_factory, context_factory, fake_datetime
):
    user1 = user_factory()
    user2 = user_factory()
    post = post_factory()
    db.session.add_all([user1, user2, post])
    db.session.commit()
    with patch("szurubooru.func.posts.serialize_post"):
        with fake_datetime("1997-12-01"):
            api.post_api.set_post_score(
                context_factory(params={"score": 1}, user=user1),
                {"post_id": post.post_id},
            )
        with fake_datetime("1997-12-02"):
            api.post_api.set_post_score(
                context_factory(params={"score": -1}, user=user2),
                {"post_id": post.post_id},
            )
        post = db.session.query(model.Post).one()
        assert db.session.query(model.PostScore).count() == 2
        assert post.score == 0


def test_trying_to_omit_mandatory_field(
    user_factory, post_factory, context_factory
):
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    with pytest.raises(errors.ValidationError):
        api.post_api.set_post_score(
            context_factory(params={}, user=user_factory()),
            {"post_id": post.post_id},
        )


def test_trying_to_update_non_existing(user_factory, context_factory):
    with pytest.raises(posts.PostNotFoundError):
        api.post_api.set_post_score(
            context_factory(params={"score": 1}, user=user_factory()),
            {"post_id": 5},
        )


def test_trying_to_rate_without_privileges(
    user_factory, post_factory, context_factory
):
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.post_api.set_post_score(
            context_factory(
                params={"score": 1},
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"post_id": post.post_id},
        )
