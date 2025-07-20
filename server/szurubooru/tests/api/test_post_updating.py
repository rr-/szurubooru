from datetime import datetime
from unittest.mock import patch

import pytest

from szurubooru import api, db, errors, model
from szurubooru.func import net, posts, snapshots, tags


@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector(
        {
            "privileges": {
                "posts:edit:tags": model.User.RANK_REGULAR,
                "posts:edit:content": model.User.RANK_REGULAR,
                "posts:edit:safety": model.User.RANK_REGULAR,
                "posts:edit:source": model.User.RANK_REGULAR,
                "posts:edit:relations": model.User.RANK_REGULAR,
                "posts:edit:notes": model.User.RANK_REGULAR,
                "posts:edit:flags": model.User.RANK_REGULAR,
                "posts:edit:thumbnail": model.User.RANK_REGULAR,
                "tags:create": model.User.RANK_MODERATOR,
                "uploads:use_downloader": model.User.RANK_REGULAR,
            },
            "allow_broken_uploads": False,
        }
    )


def test_post_updating(
    context_factory, post_factory, user_factory, fake_datetime
):
    auth_user = user_factory(rank=model.User.RANK_REGULAR)
    post = post_factory()
    db.session.add(post)
    db.session.flush()

    with patch("szurubooru.func.posts.create_post"), patch(
        "szurubooru.func.posts.update_post_tags"
    ), patch("szurubooru.func.posts.update_post_content"), patch(
        "szurubooru.func.posts.update_post_thumbnail"
    ), patch(
        "szurubooru.func.posts.update_post_safety"
    ), patch(
        "szurubooru.func.posts.update_post_source"
    ), patch(
        "szurubooru.func.posts.update_post_relations"
    ), patch(
        "szurubooru.func.posts.update_post_notes"
    ), patch(
        "szurubooru.func.posts.update_post_flags"
    ), patch(
        "szurubooru.func.posts.serialize_post"
    ), patch(
        "szurubooru.func.snapshots.modify"
    ), fake_datetime(
        "1997-01-01"
    ):
        posts.serialize_post.return_value = "serialized post"

        result = api.post_api.update_post(
            context_factory(
                params={
                    "version": 1,
                    "safety": "safe",
                    "tags": ["tag1", "tag2"],
                    "relations": [1, 2],
                    "source": "source",
                    "notes": ["note1", "note2"],
                    "flags": ["flag1", "flag2"],
                },
                files={
                    "content": "post-content",
                    "thumbnail": "post-thumbnail",
                },
                user=auth_user,
            ),
            {"post_id": post.post_id},
        )

        assert result == "serialized post"
        posts.create_post.assert_not_called()
        posts.update_post_tags.assert_called_once_with(post, ["tag1", "tag2"])
        posts.update_post_content.assert_called_once_with(post, "post-content")
        posts.update_post_thumbnail.assert_called_once_with(
            post, "post-thumbnail"
        )
        posts.update_post_safety.assert_called_once_with(post, "safe")
        posts.update_post_source.assert_called_once_with(post, "source")
        posts.update_post_relations.assert_called_once_with(post, [1, 2])
        posts.update_post_notes.assert_called_once_with(
            post, ["note1", "note2"]
        )
        posts.update_post_flags.assert_called_once_with(
            post, ["flag1", "flag2"]
        )
        posts.serialize_post.assert_called_once_with(
            post, auth_user, options=[]
        )
        snapshots.modify.assert_called_once_with(post, auth_user)
        assert post.last_edit_time == datetime(1997, 1, 1)


def test_uploading_from_url_saves_source(
    context_factory, post_factory, user_factory
):
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    with patch("szurubooru.func.net.download"), patch(
        "szurubooru.func.posts.serialize_post"
    ), patch("szurubooru.func.posts.update_post_content"), patch(
        "szurubooru.func.posts.update_post_source"
    ), patch(
        "szurubooru.func.snapshots.modify"
    ):
        net.download.return_value = b"content"
        api.post_api.update_post(
            context_factory(
                params={"contentUrl": "example.com", "version": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"post_id": post.post_id},
        )
        net.download.assert_called_once_with(
            "example.com", use_downloader=True
        )
        posts.update_post_content.assert_called_once_with(post, b"content")
        posts.update_post_source.assert_called_once_with(post, "example.com")


def test_uploading_from_url_with_source_specified(
    context_factory, post_factory, user_factory
):
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    with patch("szurubooru.func.net.download"), patch(
        "szurubooru.func.posts.serialize_post"
    ), patch("szurubooru.func.posts.update_post_content"), patch(
        "szurubooru.func.posts.update_post_source"
    ), patch(
        "szurubooru.func.snapshots.modify"
    ):
        net.download.return_value = b"content"
        api.post_api.update_post(
            context_factory(
                params={
                    "contentUrl": "example.com",
                    "source": "example2.com",
                    "version": 1,
                },
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"post_id": post.post_id},
        )
        net.download.assert_called_once_with(
            "example.com", use_downloader=True
        )
        posts.update_post_content.assert_called_once_with(post, b"content")
        posts.update_post_source.assert_called_once_with(post, "example2.com")


def test_trying_to_update_non_existing(context_factory, user_factory):
    with pytest.raises(posts.PostNotFoundError):
        api.post_api.update_post(
            context_factory(
                params="whatever",
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"post_id": 1},
        )


@pytest.mark.parametrize(
    "files,params",
    [
        ({}, {"tags": "..."}),
        ({}, {"safety": "..."}),
        ({}, {"source": "..."}),
        ({}, {"relations": "..."}),
        ({}, {"notes": "..."}),
        ({}, {"flags": "..."}),
        ({"content": "..."}, {}),
        ({"thumbnail": "..."}, {}),
    ],
)
def test_trying_to_update_field_without_privileges(
    context_factory, post_factory, user_factory, files, params
):
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    with pytest.raises(errors.AuthError):
        api.post_api.update_post(
            context_factory(
                params={**params, **{"version": 1}},
                files=files,
                user=user_factory(rank=model.User.RANK_ANONYMOUS),
            ),
            {"post_id": post.post_id},
        )


def test_trying_to_create_tags_without_privileges(
    context_factory, post_factory, user_factory
):
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    with pytest.raises(errors.AuthError), patch(
        "szurubooru.func.posts.update_post_tags"
    ):
        posts.update_post_tags.return_value = ["new-tag"]
        api.post_api.update_post(
            context_factory(
                params={"tags": ["tag1", "tag2"], "version": 1},
                user=user_factory(rank=model.User.RANK_REGULAR),
            ),
            {"post_id": post.post_id},
        )
