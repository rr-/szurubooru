from unittest.mock import patch

import pytest
import yattag

from szurubooru import api, db, model
from szurubooru.func import auth, posts


def _make_meta_tag(name, content):
    doc = yattag.Doc()
    doc.stag("meta", name=name, content=content)
    return doc.getvalue()


@pytest.mark.parametrize("view_priv", [True, False])
@pytest.mark.parametrize(
    "post_type", [model.Post.TYPE_IMAGE, model.Post.TYPE_VIDEO]
)
def test_get_post_html(
    config_injector, context_factory, post_factory, view_priv, post_type
):
    config_injector(
        {
            "name": "testing",
            "base_url": "/someprefix",
            "data_url": "data",
        }
    )
    post = post_factory(id=1, type=post_type)
    post.canvas_width = 1920
    post.canvas_height = 1080
    db.session.add(post)
    db.session.flush()
    with patch("szurubooru.func.auth.anon_has_privilege"), patch(
        "szurubooru.func.posts.get_post_content_path"
    ), patch("szurubooru.func.posts.get_post_thumbnail_path"):
        auth.anon_has_privilege.return_value = view_priv
        posts.get_post_content_path.return_value = "content-url"
        posts.get_post_thumbnail_path.return_value = "thumbnail-url"
        ret = api.opengraph_api.get_post_html(
            context_factory(), {"post_id": 1}
        )

    assert _make_meta_tag("og:site_name", "testing") in ret
    assert _make_meta_tag("og:url", "/someprefix/post/1") in ret
    assert _make_meta_tag("og:title", "testing - Post #1") in ret
    assert _make_meta_tag("twitter:title", "testing - Post #1") in ret
    assert _make_meta_tag("og:type", "article") in ret
    assert (
        bool(
            _make_meta_tag("og:article:published_time", "1996-01-01T00:00:00")
            in ret
        )
        == view_priv
    )
    if post_type == model.Post.TYPE_VIDEO:
        assert (
            bool(_make_meta_tag("twitter:card", "player") in ret) == view_priv
        )
        assert (
            bool(
                _make_meta_tag(
                    "twitter:player:stream", "/someprefix/data/content-url"
                )
                in ret
            )
            == view_priv
        )
        assert (
            bool(
                _make_meta_tag("og:video:url", "/someprefix/data/content-url")
                in ret
            )
            == view_priv
        )
        assert (
            bool(
                _make_meta_tag(
                    "og:image:url", "/someprefix/data/thumbnail-url"
                )
                in ret
            )
            == view_priv
        )
        assert (
            bool(_make_meta_tag("og:video:width", "1920") in ret) == view_priv
        )
        assert (
            bool(_make_meta_tag("og:video:height", "1080") in ret) == view_priv
        )
    else:
        assert (
            bool(_make_meta_tag("twitter:card", "summary_large_image") in ret)
            == view_priv
        )
        assert (
            bool(
                _make_meta_tag("twitter:image", "/someprefix/data/content-url")
                in ret
            )
            == view_priv
        )
