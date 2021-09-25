from unittest.mock import patch

import pytest
import yattag

from szurubooru import api, db
from szurubooru.func import auth, posts


def _make_meta_tag(name, content):
    doc = yattag.Doc()
    doc.stag("meta", name=name, content=content)
    return doc.getvalue()


@pytest.mark.parametrize("view_priv", [True, False])
def test_get_post_html(
    config_injector, context_factory, post_factory, view_priv
):
    config_injector(
        {
            "name": "test installation",
            "data_url": "data/",
        }
    )
    ctx = context_factory()
    ctx.url_prefix = "/someprefix"
    db.session.add(post_factory(id=1))
    db.session.flush()
    with patch("szurubooru.func.auth.has_privilege"), patch(
        "szurubooru.func.posts.get_post_content_url"
    ):
        auth.has_privilege.return_value = view_priv
        posts.get_post_content_url.return_value = "/content-url"
        ret = api.opengraph_api.get_post_html(ctx, {"post_id": 1})

    assert _make_meta_tag("og:site_name", "test installation") in ret
    assert _make_meta_tag("og:title", "Post 1 - test installation") in ret
    if view_priv:
        assert _make_meta_tag("og:image", "/content-url") in ret
