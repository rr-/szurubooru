import os
from datetime import datetime
from unittest.mock import patch

import pytest

from szurubooru import db, model
from szurubooru.func import (
    comments,
    files,
    image_hash,
    images,
    posts,
    tags,
    users,
    util,
)


@pytest.mark.parametrize(
    "input_mime_type,expected_url",
    [
        ("image/jpeg", "http://example.com/posts/1_244c8840887984c4.jpg"),
        ("image/gif", "http://example.com/posts/1_244c8840887984c4.gif"),
        ("totally/unknown", "http://example.com/posts/1_244c8840887984c4.dat"),
    ],
)
def test_get_post_url(input_mime_type, expected_url, config_injector):
    config_injector({"data_url": "http://example.com/", "secret": "test"})
    post = model.Post()
    post.post_id = 1
    post.mime_type = input_mime_type
    assert posts.get_post_content_url(post) == expected_url


@pytest.mark.parametrize("input_mime_type", ["image/jpeg", "image/gif"])
def test_get_post_thumbnail_url(input_mime_type, config_injector):
    config_injector({"data_url": "http://example.com/", "secret": "test"})
    post = model.Post()
    post.post_id = 1
    post.mime_type = input_mime_type
    assert (
        posts.get_post_thumbnail_url(post)
        == "http://example.com/generated-thumbnails/1_244c8840887984c4.jpg"
    )


@pytest.mark.parametrize(
    "input_mime_type,expected_path",
    [
        ("image/jpeg", "posts/1_244c8840887984c4.jpg"),
        ("image/gif", "posts/1_244c8840887984c4.gif"),
        ("totally/unknown", "posts/1_244c8840887984c4.dat"),
    ],
)
def test_get_post_content_path(input_mime_type, expected_path):
    post = model.Post()
    post.post_id = 1
    post.mime_type = input_mime_type
    assert posts.get_post_content_path(post) == expected_path


@pytest.mark.parametrize("input_mime_type", ["image/jpeg", "image/gif"])
def test_get_post_thumbnail_path(input_mime_type):
    post = model.Post()
    post.post_id = 1
    post.mime_type = input_mime_type
    assert (
        posts.get_post_thumbnail_path(post)
        == "generated-thumbnails/1_244c8840887984c4.jpg"
    )


@pytest.mark.parametrize("input_mime_type", ["image/jpeg", "image/gif"])
def test_get_post_custom_thumbnail_path(input_mime_type):
    post = model.Post()
    post.post_id = 1
    post.mime_type = input_mime_type
    assert (
        posts.get_post_custom_thumbnail_path(post)
        == "posts/custom-thumbnails/1_244c8840887984c4.dat"
    )


def test_serialize_note():
    note = model.PostNote()
    note.polygon = [[0, 1], [1, 1], [1, 0], [0, 0]]
    note.text = "..."
    assert posts.serialize_note(note) == {
        "polygon": [[0, 1], [1, 1], [1, 0], [0, 0]],
        "text": "...",
    }


def test_serialize_post_when_empty():
    assert posts.serialize_post(None, None) is None


def test_serialize_post(
    user_factory,
    comment_factory,
    tag_factory,
    tag_category_factory,
    pool_factory,
    pool_category_factory,
    config_injector,
):
    config_injector({"data_url": "http://example.com/", "secret": "test"})
    with patch("szurubooru.func.comments.serialize_comment"), patch(
        "szurubooru.func.users.serialize_micro_user"
    ), patch("szurubooru.func.posts.files.has"):
        files.has.return_value = True
        users.serialize_micro_user.side_effect = (
            lambda user, auth_user: user.name
        )
        comments.serialize_comment.side_effect = (
            lambda comment, auth_user: comment.user.name
        )

        auth_user = user_factory(name="auth user")
        post = model.Post()
        post.post_id = 1
        post.creation_time = datetime(1997, 1, 1)
        post.last_edit_time = datetime(1998, 1, 1)
        post.tags = [
            tag_factory(
                names=["tag1", "tag2"],
                category=tag_category_factory("test-cat1"),
            ),
            tag_factory(
                names=["tag3"], category=tag_category_factory("test-cat2")
            ),
        ]
        post.safety = model.Post.SAFETY_SAFE
        post.source = "4gag"
        post.type = model.Post.TYPE_IMAGE
        post.checksum = "deadbeef"
        post.checksum_md5 = "deadbeef"
        post.mime_type = "image/jpeg"
        post.file_size = 100
        post.user = user_factory(name="post author")
        post.canvas_width = 200
        post.canvas_height = 300
        post.flags = ["loop"]
        db.session.add(post)

        db.session.flush()
        db.session.add_all(
            [
                comment_factory(
                    user=user_factory(name="commenter1"),
                    post=post,
                    time=datetime(1999, 1, 1),
                ),
                comment_factory(
                    user=user_factory(name="commenter2"),
                    post=post,
                    time=datetime(1999, 1, 2),
                ),
                model.PostFavorite(
                    post=post,
                    user=user_factory(name="fav1"),
                    time=datetime(1800, 1, 1),
                ),
                model.PostFeature(
                    post=post, user=user_factory(), time=datetime(1999, 1, 1)
                ),
                model.PostScore(
                    post=post,
                    user=auth_user,
                    score=-1,
                    time=datetime(1800, 1, 1),
                ),
                model.PostScore(
                    post=post,
                    user=user_factory(),
                    score=1,
                    time=datetime(1800, 1, 1),
                ),
                model.PostScore(
                    post=post,
                    user=user_factory(),
                    score=1,
                    time=datetime(1800, 1, 1),
                ),
            ]
        )
        db.session.flush()

        pool1 = pool_factory(
            id=1,
            names=["pool1", "pool2"],
            description="desc",
            category=pool_category_factory("test-cat1"),
        )
        pool1.last_edit_time = datetime(1998, 1, 1)
        pool1.posts.append(post)

        pool2 = pool_factory(
            id=2,
            names=["pool3"],
            description="desc2",
            category=pool_category_factory("test-cat2"),
        )
        pool2.last_edit_time = datetime(1998, 1, 1)
        pool2.posts.append(post)

        db.session.add_all([pool1, pool2])
        db.session.flush()

        result = posts.serialize_post(post, auth_user)
        result["tags"].sort(key=lambda tag: tag["names"][0])

        assert result == {
            "id": 1,
            "version": 1,
            "creationTime": datetime(1997, 1, 1),
            "lastEditTime": datetime(1998, 1, 1),
            "safety": "safe",
            "source": "4gag",
            "type": "image",
            "checksum": "deadbeef",
            "checksumMD5": "deadbeef",
            "fileSize": 100,
            "canvasWidth": 200,
            "canvasHeight": 300,
            "contentUrl": "http://example.com/posts/1_244c8840887984c4.jpg",
            "thumbnailUrl": "http://example.com/"
            "generated-thumbnails/1_244c8840887984c4.jpg",
            "flags": ["loop"],
            "tags": [
                {
                    "names": ["tag1", "tag2"],
                    "category": "test-cat1",
                    "usages": 1,
                },
                {
                    "names": ["tag3"],
                    "category": "test-cat2",
                    "usages": 1,
                },
            ],
            "relations": [],
            "notes": [],
            "pools": [
                {
                    "id": 1,
                    "names": ["pool1", "pool2"],
                    "description": "desc",
                    "category": "test-cat1",
                    "postCount": 1,
                },
                {
                    "id": 2,
                    "names": ["pool3"],
                    "description": "desc2",
                    "category": "test-cat2",
                    "postCount": 1,
                },
            ],
            "user": "post author",
            "score": 1,
            "ownFavorite": False,
            "ownScore": -1,
            "tagCount": 2,
            "favoriteCount": 1,
            "commentCount": 2,
            "noteCount": 0,
            "featureCount": 1,
            "relationCount": 0,
            "lastFeatureTime": datetime(1999, 1, 1),
            "favoritedBy": ["fav1"],
            "hasCustomThumbnail": True,
            "mimeType": "image/jpeg",
            "comments": ["commenter1", "commenter2"],
        }


def test_serialize_micro_post(post_factory, user_factory):
    with patch("szurubooru.func.posts.get_post_thumbnail_url"):
        posts.get_post_thumbnail_url.return_value = (
            "https://example.com/thumb.png"
        )
        auth_user = user_factory()
        post = post_factory()
        db.session.add(post)
        db.session.flush()
        assert posts.serialize_micro_post(post, auth_user) == {
            "id": post.post_id,
            "thumbnailUrl": "https://example.com/thumb.png",
        }


def test_get_post_count(post_factory):
    previous_count = posts.get_post_count()
    db.session.add_all([post_factory(), post_factory()])
    db.session.flush()
    new_count = posts.get_post_count()
    assert previous_count == 0
    assert new_count == 2


def test_try_get_post_by_id(post_factory):
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    assert posts.try_get_post_by_id(post.post_id) == post
    assert posts.try_get_post_by_id(post.post_id + 1) is None


def test_get_post_by_id(post_factory):
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    assert posts.get_post_by_id(post.post_id) == post
    with pytest.raises(posts.PostNotFoundError):
        posts.get_post_by_id(post.post_id + 1)


def test_create_post(user_factory, fake_datetime):
    with patch("szurubooru.func.posts.update_post_content"), patch(
        "szurubooru.func.posts.update_post_tags"
    ), fake_datetime("1997-01-01"):
        auth_user = user_factory()
        post, _new_tags = posts.create_post("content", ["tag"], auth_user)
        assert post.creation_time == datetime(1997, 1, 1)
        assert post.last_edit_time is None
        posts.update_post_tags.assert_called_once_with(post, ["tag"])
        posts.update_post_content.assert_called_once_with(post, "content")


@pytest.mark.parametrize(
    "input_safety,expected_safety",
    [
        ("safe", model.Post.SAFETY_SAFE),
        ("sketchy", model.Post.SAFETY_SKETCHY),
        ("unsafe", model.Post.SAFETY_UNSAFE),
    ],
)
def test_update_post_safety(input_safety, expected_safety):
    post = model.Post()
    posts.update_post_safety(post, input_safety)
    assert post.safety == expected_safety


def test_update_post_safety_with_invalid_string():
    post = model.Post()
    with pytest.raises(posts.InvalidPostSafetyError):
        posts.update_post_safety(post, "bad")


def test_update_post_source():
    post = model.Post()
    posts.update_post_source(post, "x")
    assert post.source == "x"


def test_update_post_source_with_too_long_string():
    post = model.Post()
    with pytest.raises(posts.InvalidPostSourceError):
        posts.update_post_source(post, "x" * 3000)


@pytest.mark.parametrize(
    "is_existing,input_file,expected_mime_type,expected_type,output_file_name",
    [
        (
            True,
            "png.png",
            "image/png",
            model.Post.TYPE_IMAGE,
            "1_244c8840887984c4.png",
        ),
        (
            False,
            "png.png",
            "image/png",
            model.Post.TYPE_IMAGE,
            "1_244c8840887984c4.png",
        ),
        (
            False,
            "jpeg.jpg",
            "image/jpeg",
            model.Post.TYPE_IMAGE,
            "1_244c8840887984c4.jpg",
        ),
        (
            False,
            "gif.gif",
            "image/gif",
            model.Post.TYPE_IMAGE,
            "1_244c8840887984c4.gif",
        ),
        (
            False,
            "bmp.bmp",
            "image/bmp",
            model.Post.TYPE_IMAGE,
            "1_244c8840887984c4.bmp",
        ),
        (
            False,
            "avif.avif",
            "image/avif",
            model.Post.TYPE_IMAGE,
            "1_244c8840887984c4.avif",
        ),
        (
            False,
            "avif-avis.avif",
            "image/avif",
            model.Post.TYPE_IMAGE,
            "1_244c8840887984c4.avif",
        ),
        (
            False,
            "heic.heic",
            "image/heic",
            model.Post.TYPE_IMAGE,
            "1_244c8840887984c4.heic",
        ),
        (
            False,
            "heic-heix.heic",
            "image/heic",
            model.Post.TYPE_IMAGE,
            "1_244c8840887984c4.heic",
        ),
        (
            False,
            "heif.heif",
            "image/heif",
            model.Post.TYPE_IMAGE,
            "1_244c8840887984c4.heif",
        ),
        (
            False,
            "gif-animated.gif",
            "image/gif",
            model.Post.TYPE_ANIMATION,
            "1_244c8840887984c4.gif",
        ),
        (
            False,
            "webm.webm",
            "video/webm",
            model.Post.TYPE_VIDEO,
            "1_244c8840887984c4.webm",
        ),
        (
            False,
            "mp4.mp4",
            "video/mp4",
            model.Post.TYPE_VIDEO,
            "1_244c8840887984c4.mp4",
        ),
        (
            False,
            "flash.swf",
            "application/x-shockwave-flash",
            model.Post.TYPE_FLASH,
            "1_244c8840887984c4.swf",
        ),
    ],
)
def test_update_post_content_for_new_post(
    tmpdir,
    config_injector,
    post_factory,
    read_asset,
    is_existing,
    input_file,
    expected_mime_type,
    expected_type,
    output_file_name,
):
    with patch("szurubooru.func.util.get_sha1"), patch(
        "szurubooru.func.util.get_md5"
    ):
        util.get_sha1.return_value = "crc"
        util.get_md5.return_value = "md5"
        config_injector(
            {
                "data_dir": str(tmpdir.mkdir("data")),
                "thumbnails": {
                    "post_width": 300,
                    "post_height": 300,
                },
                "secret": "test",
                "allow_broken_uploads": False,
            }
        )
        output_file_path = "{}/data/posts/{}".format(tmpdir, output_file_name)
        post = post_factory(id=1)
        db.session.add(post)
        if is_existing:
            db.session.flush()
        assert post.post_id
        assert not os.path.exists(output_file_path)
        content = read_asset(input_file)
        posts.update_post_content(post, content)
        assert not os.path.exists(output_file_path)
        db.session.flush()
        assert post.mime_type == expected_mime_type
        assert post.type == expected_type
        assert post.checksum == "crc"
        assert post.checksum_md5 == "md5"
        assert os.path.exists(output_file_path)
        if post.type in (model.Post.TYPE_IMAGE, model.Post.TYPE_ANIMATION):
            assert db.session.query(model.PostSignature).count() == 1
        else:
            assert db.session.query(model.PostSignature).count() == 0


def test_update_post_content_to_existing_content(
    tmpdir, config_injector, post_factory, read_asset
):
    config_injector(
        {
            "data_dir": str(tmpdir.mkdir("data")),
            "data_url": "example.com",
            "thumbnails": {
                "post_width": 300,
                "post_height": 300,
            },
            "secret": "test",
            "allow_broken_uploads": False,
        }
    )
    post = post_factory()
    another_post = post_factory()
    db.session.add_all([post, another_post])
    posts.update_post_content(post, read_asset("png.png"))
    db.session.flush()
    with pytest.raises(posts.PostAlreadyUploadedError):
        posts.update_post_content(another_post, read_asset("png.png"))


@pytest.mark.parametrize("allow_broken_uploads", [True, False])
def test_update_post_content_with_broken_content(
    tmpdir, config_injector, post_factory, read_asset, allow_broken_uploads
):
    # the rationale behind this behavior is to salvage user upload even if the
    # server software thinks it's broken. chances are the server is wrong,
    # especially about flash movies.
    config_injector(
        {
            "data_dir": str(tmpdir.mkdir("data")),
            "thumbnails": {
                "post_width": 300,
                "post_height": 300,
            },
            "secret": "test",
            "allow_broken_uploads": allow_broken_uploads,
        }
    )
    post = post_factory()
    another_post = post_factory()
    db.session.add_all([post, another_post])
    if allow_broken_uploads:
        posts.update_post_content(post, read_asset("png-broken.png"))
        db.session.flush()
        assert post.canvas_width is None
        assert post.canvas_height is None
    else:
        with pytest.raises(posts.InvalidPostContentError):
            posts.update_post_content(post, read_asset("png-broken.png"))
        db.session.flush()


@pytest.mark.parametrize("input_content", [None, b"not a media file"])
def test_update_post_content_with_invalid_content(
    config_injector, input_content
):
    config_injector(
        {
            "allow_broken_uploads": True,
        }
    )
    post = model.Post()
    with pytest.raises(posts.InvalidPostContentError):
        posts.update_post_content(post, input_content)


@pytest.mark.parametrize("is_existing", (True, False))
def test_update_post_thumbnail_to_new_one(
    tmpdir, config_injector, read_asset, post_factory, is_existing
):
    config_injector(
        {
            "data_dir": str(tmpdir.mkdir("data")),
            "thumbnails": {
                "post_width": 300,
                "post_height": 300,
            },
            "secret": "test",
            "allow_broken_uploads": False,
        }
    )
    post = post_factory(id=1)
    db.session.add(post)
    if is_existing:
        db.session.flush()
    assert post.post_id
    generated_path = (
        "{}/data/generated-thumbnails/".format(tmpdir)
        + "1_244c8840887984c4.jpg"
    )
    source_path = (
        "{}/data/posts/custom-thumbnails/".format(tmpdir)
        + "1_244c8840887984c4.dat"
    )
    assert not os.path.exists(generated_path)
    assert not os.path.exists(source_path)
    posts.update_post_content(post, read_asset("png.png"))
    posts.update_post_thumbnail(post, read_asset("jpeg.jpg"))
    assert not os.path.exists(generated_path)
    assert not os.path.exists(source_path)
    db.session.flush()
    assert os.path.exists(generated_path)
    assert os.path.exists(source_path)
    with open(source_path, "rb") as handle:
        assert handle.read() == read_asset("jpeg.jpg")


@pytest.mark.parametrize("is_existing", (True, False))
def test_update_post_thumbnail_to_default(
    tmpdir, config_injector, read_asset, post_factory, is_existing
):
    config_injector(
        {
            "data_dir": str(tmpdir.mkdir("data")),
            "thumbnails": {
                "post_width": 300,
                "post_height": 300,
            },
            "secret": "test",
            "allow_broken_uploads": False,
        }
    )
    post = post_factory(id=1)
    db.session.add(post)
    if is_existing:
        db.session.flush()
    assert post.post_id
    generated_path = (
        "{}/data/generated-thumbnails/".format(tmpdir)
        + "1_244c8840887984c4.jpg"
    )
    source_path = (
        "{}/data/posts/custom-thumbnails/".format(tmpdir)
        + "1_244c8840887984c4.dat"
    )
    assert not os.path.exists(generated_path)
    assert not os.path.exists(source_path)
    posts.update_post_content(post, read_asset("png.png"))
    posts.update_post_thumbnail(post, read_asset("jpeg.jpg"))
    posts.update_post_thumbnail(post, None)
    assert not os.path.exists(generated_path)
    assert not os.path.exists(source_path)
    db.session.flush()
    assert os.path.exists(generated_path)
    assert not os.path.exists(source_path)


@pytest.mark.parametrize("is_existing", (True, False))
def test_update_post_thumbnail_with_broken_thumbnail(
    tmpdir, config_injector, read_asset, post_factory, is_existing
):
    config_injector(
        {
            "data_dir": str(tmpdir.mkdir("data")),
            "thumbnails": {
                "post_width": 300,
                "post_height": 300,
            },
            "secret": "test",
            "allow_broken_uploads": False,
        }
    )
    post = post_factory(id=1)
    db.session.add(post)
    if is_existing:
        db.session.flush()
    assert post.post_id
    generated_path = (
        "{}/data/generated-thumbnails/".format(tmpdir)
        + "1_244c8840887984c4.jpg"
    )
    source_path = (
        "{}/data/posts/custom-thumbnails/".format(tmpdir)
        + "1_244c8840887984c4.dat"
    )
    assert not os.path.exists(generated_path)
    assert not os.path.exists(source_path)
    posts.update_post_content(post, read_asset("png.png"))
    posts.update_post_thumbnail(post, read_asset("png-broken.png"))
    assert not os.path.exists(generated_path)
    assert not os.path.exists(source_path)
    db.session.flush()
    assert os.path.exists(generated_path)
    assert os.path.exists(source_path)
    with open(source_path, "rb") as handle:
        assert handle.read() == read_asset("png-broken.png")
    with open(generated_path, "rb") as handle:
        image = images.Image(handle.read())
        assert image.width == 1
        assert image.height == 1


def test_update_post_content_leaving_custom_thumbnail(
    tmpdir, config_injector, read_asset, post_factory
):
    config_injector(
        {
            "data_dir": str(tmpdir.mkdir("data")),
            "thumbnails": {
                "post_width": 300,
                "post_height": 300,
            },
            "secret": "test",
            "allow_broken_uploads": False,
        }
    )
    post = post_factory(id=1)
    db.session.add(post)
    posts.update_post_content(post, read_asset("png.png"))
    posts.update_post_thumbnail(post, read_asset("jpeg.jpg"))
    posts.update_post_content(post, read_asset("png.png"))
    db.session.flush()
    generated_path = (
        "{}/data/generated-thumbnails/".format(tmpdir)
        + "1_244c8840887984c4.jpg"
    )
    source_path = (
        "{}/data/posts/custom-thumbnails/".format(tmpdir)
        + "1_244c8840887984c4.dat"
    )
    assert os.path.exists(source_path)
    assert os.path.exists(generated_path)


@pytest.mark.parametrize("filename", ("avif.avif", "heic.heic", "heif.heif"))
def test_update_post_content_convert_heif_to_png_when_processing(
    tmpdir, config_injector, read_asset, post_factory, filename
):
    config_injector(
        {
            "data_dir": str(tmpdir.mkdir("data")),
            "thumbnails": {
                "post_width": 300,
                "post_height": 300,
            },
            "secret": "test",
            "allow_broken_uploads": False,
        }
    )
    post = post_factory(id=1)
    db.session.add(post)
    posts.update_post_content(post, read_asset(filename))
    posts.update_post_thumbnail(post, read_asset(filename))
    db.session.flush()
    generated_path = (
        "{}/data/generated-thumbnails/".format(tmpdir)
        + "1_244c8840887984c4.jpg"
    )
    source_path = (
        "{}/data/posts/custom-thumbnails/".format(tmpdir)
        + "1_244c8840887984c4.dat"
    )
    assert os.path.exists(source_path)
    assert os.path.exists(generated_path)


def test_update_post_tags(tag_factory):
    post = model.Post()
    with patch("szurubooru.func.tags.get_or_create_tags_by_names"):
        tags.get_or_create_tags_by_names.side_effect = lambda tag_names: (
            [tag_factory(names=[name]) for name in tag_names],
            [],
        )
        posts.update_post_tags(post, ["tag1", "tag2"])
    assert len(post.tags) == 2
    assert post.tags[0].names[0].name == "tag1"
    assert post.tags[1].names[0].name == "tag2"


def test_update_post_relations(post_factory):
    relation1 = post_factory()
    relation2 = post_factory()
    db.session.add_all([relation1, relation2])
    db.session.flush()
    post = post_factory()
    posts.update_post_relations(post, [relation1.post_id, relation2.post_id])
    assert len(post.relations) == 2
    assert sorted(r.post_id for r in post.relations) == [
        relation1.post_id,
        relation2.post_id,
    ]


def test_update_post_relations_bidirectionality(post_factory):
    relation1 = post_factory()
    relation2 = post_factory()
    db.session.add_all([relation1, relation2])
    db.session.flush()
    post = post_factory()
    posts.update_post_relations(post, [relation1.post_id, relation2.post_id])
    db.session.flush()
    posts.update_post_relations(relation1, [])
    assert len(post.relations) == 1
    assert post.relations[0].post_id == relation2.post_id


def test_update_post_relations_with_nonexisting_posts():
    post = model.Post()
    with pytest.raises(posts.InvalidPostRelationError):
        posts.update_post_relations(post, [100])


def test_update_post_relations_with_itself(post_factory):
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    with pytest.raises(posts.InvalidPostRelationError):
        posts.update_post_relations(post, [post.post_id])


def test_update_post_notes():
    post = model.Post()
    posts.update_post_notes(
        post,
        [
            {"polygon": [[0, 0], [0, 1], [1, 0], [0, 0]], "text": "text1"},
            {"polygon": [[0, 0], [0, 1], [1, 0], [0, 0]], "text": "text2"},
        ],
    )
    assert len(post.notes) == 2
    assert post.notes[0].polygon == [[0, 0], [0, 1], [1, 0], [0, 0]]
    assert post.notes[0].text == "text1"
    assert post.notes[1].polygon == [[0, 0], [0, 1], [1, 0], [0, 0]]
    assert post.notes[1].text == "text2"


@pytest.mark.parametrize(
    "input",
    [
        [{"text": "..."}],
        [{"polygon": None, "text": "..."}],
        [{"polygon": "trash", "text": "..."}],
        [{"polygon": ["trash", "trash", "trash"], "text": "..."}],
        [{"polygon": {2: "trash", 3: "trash", 4: "trash"}, "text": "..."}],
        [{"polygon": [[0, 0]], "text": "..."}],
        [{"polygon": [[0, 0], [0, 0], None], "text": "..."}],
        [{"polygon": [[0, 0], [0, 0], "surprise"], "text": "..."}],
        [
            {
                "polygon": [[0, 0], [0, 0], {2: "trash", 3: "trash"}],
                "text": "...",
            }
        ],
        [{"polygon": [[0, 0], [0, 0], 5], "text": "..."}],
        [{"polygon": [[0, 0], [0, 0], [0, 2]], "text": "..."}],
        [{"polygon": [[0, 0], [0, 0], [0, "..."]], "text": "..."}],
        [{"polygon": [[0, 0], [0, 0], [0, 0, 0]], "text": "..."}],
        [{"polygon": [[0, 0], [0, 0], [0]], "text": "..."}],
        [{"polygon": [[0, 0], [0, 0], [0, 1]], "text": ""}],
        [{"polygon": [[0, 0], [0, 0], [0, 1]], "text": None}],
        [{"polygon": [[0, 0], [0, 0], [0, 1]]}],
    ],
)
def test_update_post_notes_with_invalid_content(input):
    post = model.Post()
    with pytest.raises(posts.InvalidPostNoteError):
        posts.update_post_notes(post, input)


def test_update_post_flags():
    post = model.Post()
    posts.update_post_flags(post, ["loop"])
    assert post.flags == ["loop"]


def test_update_post_flags_with_invalid_content():
    post = model.Post()
    with pytest.raises(posts.InvalidPostFlagError):
        posts.update_post_flags(post, ["invalid"])


def test_feature_post(post_factory, user_factory):
    post = post_factory()
    user = user_factory()
    previous_featured_post = posts.try_get_featured_post()
    db.session.flush()
    posts.feature_post(post, user)
    db.session.flush()
    new_featured_post = posts.try_get_featured_post()
    assert previous_featured_post is None
    assert new_featured_post == post


def test_delete(post_factory, config_injector):
    config_injector({"delete_source_files": False})
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    assert posts.get_post_count() == 1
    posts.delete(post)
    db.session.flush()
    assert posts.get_post_count() == 0


def test_merge_posts_deletes_source_post(post_factory, config_injector):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    db.session.add_all([source_post, target_post])
    db.session.flush()
    posts.merge_posts(source_post, target_post, False)
    db.session.flush()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    post = posts.get_post_by_id(target_post.post_id)
    assert post is not None


def test_merge_posts_with_itself(post_factory, config_injector):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    db.session.add(source_post)
    db.session.flush()
    with pytest.raises(posts.InvalidPostRelationError):
        posts.merge_posts(source_post, source_post, False)


def test_merge_posts_moves_tags(post_factory, tag_factory, config_injector):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    tag = tag_factory()
    tag.posts = [source_post]
    db.session.add_all([source_post, target_post, tag])
    db.session.commit()
    assert source_post.tag_count == 1
    assert target_post.tag_count == 0
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).tag_count == 1


def test_merge_posts_doesnt_duplicate_tags(
    post_factory, tag_factory, config_injector
):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    tag = tag_factory()
    tag.posts = [source_post, target_post]
    db.session.add_all([source_post, target_post, tag])
    db.session.commit()
    assert source_post.tag_count == 1
    assert target_post.tag_count == 1
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).tag_count == 1


def test_merge_posts_moves_comments(
    post_factory, comment_factory, config_injector
):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    comment = comment_factory(post=source_post)
    db.session.add_all([source_post, target_post, comment])
    db.session.commit()
    assert source_post.comment_count == 1
    assert target_post.comment_count == 0
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).comment_count == 1


def test_merge_posts_moves_scores(
    post_factory, post_score_factory, config_injector
):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    score = post_score_factory(post=source_post, score=1)
    db.session.add_all([source_post, target_post, score])
    db.session.commit()
    assert source_post.score == 1
    assert target_post.score == 0
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).score == 1


def test_merge_posts_doesnt_duplicate_scores(
    post_factory, user_factory, post_score_factory, config_injector
):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    user = user_factory()
    score1 = post_score_factory(post=source_post, score=1, user=user)
    score2 = post_score_factory(post=target_post, score=1, user=user)
    db.session.add_all([source_post, target_post, score1, score2])
    db.session.commit()
    assert source_post.score == 1
    assert target_post.score == 1
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).score == 1


def test_merge_posts_moves_favorites(
    post_factory, post_favorite_factory, config_injector
):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    favorite = post_favorite_factory(post=source_post)
    db.session.add_all([source_post, target_post, favorite])
    db.session.commit()
    assert source_post.favorite_count == 1
    assert target_post.favorite_count == 0
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).favorite_count == 1


def test_merge_posts_doesnt_duplicate_favorites(
    post_factory, user_factory, post_favorite_factory, config_injector
):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    user = user_factory()
    favorite1 = post_favorite_factory(post=source_post, user=user)
    favorite2 = post_favorite_factory(post=target_post, user=user)
    db.session.add_all([source_post, target_post, favorite1, favorite2])
    db.session.commit()
    assert source_post.favorite_count == 1
    assert target_post.favorite_count == 1
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).favorite_count == 1


def test_merge_posts_moves_child_relations(post_factory, config_injector):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    related_post = post_factory()
    source_post.relations = [related_post]
    db.session.add_all([source_post, target_post, related_post])
    db.session.commit()
    assert source_post.relation_count == 1
    assert target_post.relation_count == 0
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).relation_count == 1


def test_merge_posts_doesnt_duplicate_child_relations(
    post_factory, config_injector
):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    related_post = post_factory()
    source_post.relations = [related_post]
    target_post.relations = [related_post]
    db.session.add_all([source_post, target_post, related_post])
    db.session.commit()
    assert source_post.relation_count == 1
    assert target_post.relation_count == 1
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).relation_count == 1


def test_merge_posts_moves_parent_relations(post_factory, config_injector):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    related_post = post_factory()
    related_post.relations = [source_post]
    db.session.add_all([source_post, target_post, related_post])
    db.session.commit()
    assert source_post.relation_count == 1
    assert target_post.relation_count == 0
    assert related_post.relation_count == 1
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).relation_count == 1
    assert posts.get_post_by_id(related_post.post_id).relation_count == 1


def test_merge_posts_doesnt_duplicate_parent_relations(
    post_factory, config_injector
):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    related_post = post_factory()
    related_post.relations = [source_post, target_post]
    db.session.add_all([source_post, target_post, related_post])
    db.session.commit()
    assert source_post.relation_count == 1
    assert target_post.relation_count == 1
    assert related_post.relation_count == 2
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).relation_count == 1
    assert posts.get_post_by_id(related_post.post_id).relation_count == 1


def test_merge_posts_doesnt_create_relation_loop_for_children(
    post_factory, config_injector
):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    source_post.relations = [target_post]
    db.session.add_all([source_post, target_post])
    db.session.commit()
    assert source_post.relation_count == 1
    assert target_post.relation_count == 1
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).relation_count == 0


def test_merge_posts_doesnt_create_relation_loop_for_parents(
    post_factory, config_injector
):
    config_injector({"delete_source_files": False})
    source_post = post_factory()
    target_post = post_factory()
    target_post.relations = [source_post]
    db.session.add_all([source_post, target_post])
    db.session.commit()
    assert source_post.relation_count == 1
    assert target_post.relation_count == 1
    posts.merge_posts(source_post, target_post, False)
    db.session.commit()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    assert posts.get_post_by_id(target_post.post_id).relation_count == 0


def test_merge_posts_replaces_content(
    post_factory, config_injector, tmpdir, read_asset
):
    config_injector(
        {
            "data_dir": str(tmpdir.mkdir("data")),
            "data_url": "example.com",
            "delete_source_files": False,
            "thumbnails": {
                "post_width": 300,
                "post_height": 300,
            },
            "secret": "test",
        }
    )
    source_post = post_factory(id=1)
    target_post = post_factory(id=2)
    content = read_asset("png.png")
    db.session.add_all([source_post, target_post])
    db.session.commit()
    posts.update_post_content(source_post, content)
    db.session.flush()
    source_path = os.path.join(
        "{}/data/posts/1_244c8840887984c4.png".format(tmpdir)
    )
    target_path1 = os.path.join(
        "{}/data/posts/2_49caeb3ec1643406.png".format(tmpdir)
    )
    target_path2 = os.path.join(
        "{}/data/posts/2_49caeb3ec1643406.dat".format(tmpdir)
    )
    assert os.path.exists(source_path)
    assert not os.path.exists(target_path1)
    assert not os.path.exists(target_path2)
    posts.merge_posts(source_post, target_post, True)
    db.session.flush()
    assert posts.try_get_post_by_id(source_post.post_id) is None
    post = posts.get_post_by_id(target_post.post_id)
    assert post is not None
    assert os.path.exists(source_path)
    assert os.path.exists(target_path1)
    assert not os.path.exists(target_path2)


def test_search_by_image(post_factory, config_injector, read_asset):
    config_injector({"allow_broken_uploads": False})
    post = post_factory()
    posts.generate_post_signature(post, read_asset("jpeg.jpg"))
    db.session.flush()

    result1 = posts.search_by_image(read_asset("jpeg-similar.jpg"))
    assert len(result1) == 1
    result1_distance, result1_post = result1[0]
    assert abs(result1_distance - 0.19713075553164386) < 1e-8
    assert result1_post.post_id == post.post_id

    result2 = posts.search_by_image(read_asset("png.png"))
    assert not result2
