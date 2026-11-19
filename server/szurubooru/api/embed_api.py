import logging
from pathlib import Path
import re
import html
from urllib.parse import quote
from typing import Dict, Optional

from szurubooru import config, model, rest
from szurubooru.func import (
    auth,
    posts,
    serialization,
)

if (Path(config.config['client_dir']) / "index.htm").exists():
    with open(f"{config.config['client_dir']}/index.htm") as index:
        index_html = index.read()
else:
    logging.warning("Could not find index.htm needed for embeds.")

def _index_path(params: Dict[str, str]) -> int:
    try:
        return params["path"]
    except (TypeError, ValueError):
        raise posts.InvalidPostIdError(
            "Invalid post ID."
        )


def _get_post(post_id: int) -> model.Post:
    return posts.get_post_by_id(post_id)


def _get_post_id(match: re.Match) -> int:
    post_id = match.group("post_id")
    try:
        return int(post_id)
    except (TypeError, ValueError):
        raise posts.InvalidPostIdError(
            "Invalid post ID: %r." % post_id
        )


def _serialize_post(
    ctx: rest.Context, post: Optional[model.Post]
) -> rest.Response:
    return posts.serialize_post(
        post, ctx.user, options=["thumbnailUrl", "user"]
    )


@rest.routes.get("/oembed/?")
def get_post(
    ctx: rest.Context, _params: Dict[str, str] = {}, url: str = ""
) -> rest.Response:
    auth.verify_privilege(ctx.user, "posts:view")

    url = url or ctx.get_param_as_string("url")
    match = re.match(r".*?/post/(?P<post_id>\d+)", url)
    if not match:
        raise posts.InvalidPostIdError("Invalid post ID.")

    post_id = _get_post_id(match)
    post = _get_post(post_id)
    serialized = _serialize_post(ctx, post)
    embed = {
        "version": "1.0",
        "type": "photo",
        "title": f"{config.config['name']} – Post #{post_id}",
        "author_name": serialized["user"]["name"] if serialized["user"] else None,
        "provider_name": config.config["name"],
        "provider_url": config.config["homepage_url"],
        "thumbnail_url": f"{config.config['site_url']}/{serialized['thumbnailUrl']}",
        "thumbnail_width": int(config.config["thumbnails"]["post_width"]),
        "thumbnail_height": int(config.config["thumbnails"]["post_height"]),
        "url": f"{config.config['site_url']}/{serialized['thumbnailUrl']}",
        "width": int(config.config["thumbnails"]["post_width"]),
        "height": int(config.config["thumbnails"]["post_height"])
    }
    return embed


@rest.routes.get("/index(?P<path>/.+)")
def post_index(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    path = _index_path(params)

    if not index_html:
        logging.info("Embed was requested but index.htm file does not exist. Redirecting to 404.")
        return {"return_type": "custom", "status_code": "404", "content": [("content-type", "text/html")]}

    try:
        oembed = get_post(ctx, {}, path)
    except posts.PostNotFoundError:
        return {"return_type": "custom", "status_code": "404", "content": index_html}

    url = config.config["site_url"] + path
    new_html = index_html.replace("</head>", f'''
<meta property="og:site_name" content="{config.config["name"]}">
<meta property="og:url" content="{html.escape(url)}">
<meta property="og:type" content="article">
<meta property="og:title" content="{html.escape(oembed['title'])}">
<meta name="twitter:title" content="{html.escape(oembed['title'])}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="{html.escape(oembed['url'])}">
<meta property="og:image:url" content="{html.escape(oembed['url'])}">
<meta property="og:image:width" content="{oembed['width']}">
<meta property="og:image:height" content="{oembed['height']}">
<meta property="article:author" content="{html.escape(oembed['author_name'] or '')}">
<link rel="alternate" type="application/json+oembed" href="{config.config["site_url"]}/api/oembed?url={quote(html.escape(url))}" title="{html.escape(config.config["name"])}"></head>
''').replace("<html>", '<html prefix="og: http://ogp.me/ns#">').replace("<title>Loading...</title>", f"<title>{html.escape(oembed['title'])}</title>")
    return {"return_type": "custom", "content": new_html}
