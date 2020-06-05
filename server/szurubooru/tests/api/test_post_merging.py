from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import posts, snapshots


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({"privileges": {"posts:merge": model.User.RANK_REGULAR}})


def test_merging(user_factory, context_factory, post_factory):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    source_post = post_factory()
    target_post = post_factory()
    db.session.add_all([source_post, target_post])
    db.session.flush()
    with patch("szurubooru.func.posts.serialize_post"), patch(
        "szurubooru.func.posts.merge_posts"
    ), patch("szurubooru.func.snapshots.merge"):
        api.post_api.merge_posts(
            context_factory(
                params={
                    "removeVersion": 1,
                    "mergeToVersion": 1,
                    "remove": source_post.post_id,
                    "mergeTo": target_post.post_id,
                    "replaceContent": False,
                },
                user=auth_user,
            )
        )
        posts.merge_posts.called_once_with(source_post, target_post)
        snapshots.merge.assert_called_once_with(
            source_post, target_post, auth_user
        )


@pytest.mark.parametrize(
    "field", ["remove", "mergeTo", "removeVersion", "mergeToVersion"]
)
def test_trying_to_omit_mandatory_field(
    user_factory, post_factory, context_factory, field
):
    source_post = post_factory()
    target_post = post_factory()
    db.session.add_all([source_post, target_post])
    db.session.commit()
    params = {
        "removeVersion": 1,
        "mergeToVersion": 1,
        "remove": source_post.post_id,
        "mergeTo": target_post.post_id,
        "replaceContent": False,
    }
    del params[field]
    with pytest.raises(errors.ValidationError):
        api.post_api.merge_posts(
            context_factory(
                params=params, user=user_factory(rank=model.User.RANK_REGULAR)
            )
        )


def test_trying_to_merge_non_existing(
    user_factory, post_factory, context_factory
):
    post = post_factory()
    db.session.add(post)
    db.session.commit()
    with pytest.raises(posts.PostNotFoundError):
        api.post_api.merge_posts(
            context_factory(
                params={"remove": post.post_id, "mergeTo": 999},
                user=user_factory(rank=model.User.RANK_REGULAR),
            )
        )
    with pytest.raises(posts.PostNotFoundError):
        api.post_api.merge_posts(
            context_factory(
                params={"remove": 999, "mergeTo": post.post_id},
                user=user_factory(rank=model.User.RANK_REGULAR),
            )
        )


def test_trying_to_merge_without_privileges(
    user_factory, post_factory, context_factory
):
    source_post = post_factory()
    target_post = post_factory()
    db.session.add_all([source_post, target_post])
    db.session.commit()
    with pytest.raises(errors.AuthError):
        api.post_api.merge_posts(
            context_factory(
                params={
                    "removeVersion": 1,
                    "mergeToVersion": 1,
                    "remove": source_post.post_id,
                    "mergeTo": target_post.post_id,
                    "replaceContent": False,
                },
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            )
        )
