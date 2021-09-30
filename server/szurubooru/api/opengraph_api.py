from typing import Callable, Dict

from yattag import Doc

from szurubooru import config, model, rest
from szurubooru.func import auth, posts, util

_default_meta_tags = {
    "viewport": "width=device-width, initial-scale=1, maximum-scale=1",
    "theme-color": "#24aadd",
    "apple-mobile-web-app-capable": "yes",
    "apple-mobile-web-app-status-bar-style": "black",
    "msapplication-TileColor": "#ffffff",
    "msapplication-TileImage": "/img/mstile-150x150.png",
}


_apple_touch_startup_images = {
    "640x1136": {
        "device-width": "320px",
        "device-height": "568px",
        "-webkit-device-pixel-ratio": 2,
        "orientation": "portrait",
    },
    "750x1294": {
        "device-width": "375px",
        "device-height": "667px",
        "-webkit-device-pixel-ratio": 2,
        "orientation": "portrait",
    },
    "1242x2148": {
        "device-width": "414px",
        "device-height": "736px",
        "-webkit-device-pixel-ratio": 3,
        "orientation": "portrait",
    },
    "1125x2436": {
        "device-width": "375px",
        "device-height": "812px",
        "-webkit-device-pixel-ratio": 3,
        "orientation": "portrait",
    },
    "1536x2048": {
        "min-device-width": "768px",
        "max-device-width": "1024px",
        "-webkit-min-device-pixel-ratio": 2,
        "orientation": "portrait",
    },
    "1668x2224": {
        "min-device-width": "834px",
        "max-device-width": "834px",
        "-webkit-min-device-pixel-ratio": 2,
        "orientation": "portrait",
    },
    "2048x2732": {
        "min-device-width": "1024px",
        "max-device-width": "1024px",
        "-webkit-min-device-pixel-ratio": 2,
        "orientation": "portrait",
    },
}


def _get_html_template(
    title: str,
    meta_tags: Dict = {},
) -> Doc:
    doc = Doc()
    doc.asis("<!DOCTYPE html>")
    with doc.tag("html"):
        with doc.tag("head"):
            doc.stag("meta", charset="utf-8")
            for name, content in {**_default_meta_tags, **meta_tags}.items():
                doc.stag("meta", name=name, content=content)
            with doc.tag("title"):
                doc.text(title)
            doc.stag("base", href=util.add_url_prefix())
            doc.stag(
                "link",
                rel="manifest",
                href=util.add_url_prefix("/api/manifest.json"),
            )
            doc.stag(
                "link",
                href=util.add_url_prefix("/css/app.min.css"),
                rel="stylesheet",
                type="text/css",
            )
            doc.stag(
                "link",
                href=util.add_url_prefix("/css/vendor.min.css"),
                rel="stylesheet",
                type="text/css",
            )
            doc.stag(
                "link",
                rel="shortcut icon",
                type="image/png",
                href=util.add_url_prefix("/img/favicon.png"),
            )
            doc.stag(
                "link",
                rel="apple-touch-icon",
                sizes="180x180",
                href=util.add_url_prefix("/img/apple-touch-icon.png"),
            )
            for res, media in _apple_touch_startup_images.items():
                doc.stag(
                    "link",
                    rel="apple-touch-startup-image",
                    href=util.add_url_prefix(
                        f"/img/apple-touch-startup-image-{res}.png"
                    ),
                    media=" and ".join(
                        f"({k}: {v})" for k, v in media.items()
                    ),
                )
        with doc.tag("body"):
            with doc.tag("div", id="top-navigation-holder"):
                pass
            with doc.tag("div", id="content-holder"):
                pass
            with doc.tag(
                "script",
                type="text/javascript",
                src=util.add_url_prefix("js/vendor.min.js"),
            ):
                pass
            with doc.tag(
                "script",
                type="text/javascript",
                src=util.add_url_prefix("js/app.min.js"),
            ):
                pass
    return doc.getvalue()


def _get_post_id(params: Dict[str, str]) -> int:
    try:
        return int(params["post_id"])
    except TypeError:
        raise posts.InvalidPostIdError(
            "Invalid post ID: %r." % params["post_id"]
        )


def _get_post(params: Dict[str, str]) -> model.Post:
    return posts.get_post_by_id(_get_post_id(params))


@rest.routes.get("/html/post/(?P<post_id>[^/]+)/?", accept="text/html")
def get_post_html(
    ctx: rest.Context, params: Dict[str, str] = {}
) -> rest.Response:
    try:
        post = _get_post(params)
        title = f"{config.config['name']} - post {_get_post_id(params)}"
    except posts.InvalidPostIdError:
        # Return the default template and let the browser JS handle the 404
        return _get_html_template()

    metadata = {
        "og:site_name": config.config["name"],
        "og:url": util.add_url_prefix(f"post/{params['post_id']}"),
        "og:title": title,
        "twitter:title": title,
        "og:type": "article",
    }
    # Note: ctx.user will always be the anonymous user
    if auth.has_privilege(ctx.user, "posts:view"):
        content_url = util.add_data_prefix(posts.get_post_content_path(post))
        thumbnail_url = util.add_data_prefix(
            posts.get_post_thumbnail_path(post)
        )
        metadata["og:article:published_time"] = post.creation_time.isoformat()
        if post.last_edit_time:
            metadata[
                "og:article:modified_time"
            ] = post.last_edit_time.isoformat()
        metadata["og:image:alt"] = " ".join(
            tag.first_name for tag in post.tags
        )
        if post.type in (model.Post.TYPE_VIDEO):
            metadata["twitter:card"] = "player"
            metadata["og:video:url"] = content_url
            metadata["twitter:player:stream"] = content_url
            metadata["og:image:url"] = thumbnail_url
            if post.canvas_width and post.canvas_height:
                metadata["og:video:width"] = str(post.canvas_width)
                metadata["og:video:height"] = str(post.canvas_height)
                metadata["twitter:player:width"] = str(post.canvas_width)
                metadata["twitter:player:height"] = str(post.canvas_height)
        else:
            metadata["twitter:card"] = "summary_large_image"
            metadata["og:image:url"] = content_url
            metadata["twitter:image"] = content_url
    return _get_html_template(title=title, meta_tags=metadata)


@rest.routes.get("/html/.*", accept="text/html")
def default_route(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    return _get_html_template(title=config.config["name"])
