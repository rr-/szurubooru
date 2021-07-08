import os
import os.path
import html
import urllib.parse
import itertools
from typing import Dict, Any, Callable, Tuple, List, Iterable
from http import HTTPStatus
from pathlib import Path

import requests

FRONTEND_WEB_ROOT = Path(os.environ["SZURUBOORU_WEB_ROOT"])

with open(FRONTEND_WEB_ROOT / "index.htm") as f:
    INDEX_HTML = f.read()

BACKEND_BASE_URL = os.environ["SZURUBOORU_BASE_URL"]
PUBLIC_BASE_URL = os.environ["SZURUBOORU_PUBLIC_BASE_URL"]

Metadata = Iterable[Tuple[str, str]]

def general_embed(server_info: Dict[str, Any]) -> Metadata:
    yield "og:site_name", server_info["config"]["name"]

def user_embed(username: str) -> Metadata:
    yield "og:type", "profile"
    yield "profile:username", username
    yield "og:title", username
    yield "og:image:height", "128"
    yield "og:image:width", "128"
    user = requests.get(BACKEND_BASE_URL + "/api/user/" + username).json()
    yield "og:image:url", urllib.parse.join(PUBLIC_BASE_URL, user["avatarUrl"])

def _image_embed(post: Dict[str, Any]) -> Metadata:
    url = PUBLIC_BASE_URL + post["contentUrl"]

    if post["type"] == "video":
        prefix = "og:video"
        yield "og:image", PUBLIC_BASE_URL + post["thumbnailUrl"]
        yield "twitter:card", "player"
    else:
        prefix = "og:image"
        yield "twitter:card", "summary_large_image"

    yield prefix + ":width", str(post["canvasWidth"])
    yield prefix + ":height", str(post["canvasHeight"])
    yield prefix, url
    if BACKEND_BASE_URL.startswith('https://'):
        yield prefix + ":secure_url", url
    yield prefix + ":type", post["mimeType"]

def _author_embed(user: Dict[str, Any]) -> Metadata:
    yield "article:author", PUBLIC_BASE_URL + "/user/" + user["name"]

def post_embed(post_id: int) -> Metadata:
    post = requests.get(BACKEND_BASE_URL + f"/api/post/{post_id}").json()
    yield "og:type", "article"
    yield from _author_embed(post["user"])
    yield "og:title", post["user"]["name"]
    if post["tags"]:
        value = "Tags: " + ", ".join(tag["names"][0] for tag in post["tags"])
        yield "og:description", value
        yield "description", value
    yield from _image_embed(post)

def homepage_embed(server_info: Dict[str, Any]) -> Metadata:
    yield "og:title", server_info["config"]["name"]
    yield "og:type", "website"
    post = server_info["featuredPost"]
    if post is not None:
        yield from _image_embed(post)

def render_embed(metadata: Metadata) -> str:
    out = []
    for k, v in metadata:
        k, v = html.escape(k), html.escape(v)
        out.append(f'<meta property="{k}" content="{v}">')
    return ''.join(out)

def application(env: Dict[str, Any], start_response: Callable[[str, Any], Any]) -> Tuple[bytes]:
    def serve_file(path):
        start_response(str(int(HTTPStatus.OK)), [("X-Accel-Redirect", path)])
        return ()

    def serve_without_embed():
        return serve_file("/index.htm")

    method = env["REQUEST_METHOD"]
    if method != "GET":
        start_response(str(int(HTTPStatus.BAD_REQUEST)), [("Content-Type", "text/plain")])
        return (b"Bad request",)

    path = env["PATH_INFO"].lstrip("/")
    path = path.encode("latin-1").decode("utf-8")  # PEP-3333

    if path and (FRONTEND_WEB_ROOT / path).exists():
        return serve_file("/" + path)

    path = "/" + path
    path_components = path.split("/")

    if path_components[1] not in {"post", "user", ""}:
        # serve index.htm like normal
        return serve_without_embed()

    server_info = requests.get(BACKEND_BASE_URL + "/api/info").json()
    privileges = server_info["config"]["privileges"]
    if path_components[1] == "user":
        username = path_components[2]
        if privileges["users:view"] != "anonymous" or not username:
            return serve_without_embed()
        metadata = user_embed(username)

    elif path_components[1] == "post":
        try:
            post_id = int(path_components[2])
        except ValueError:
            return serve_without_embed()

        if privileges["posts:view"] != "anonymous":
            return serve_without_embed()
        metadata = post_embed(post_id)

    elif path_components[1] == "":
        metadata = homepage_embed(server_info)

    metadata = itertools.chain(general_embed(server_info), metadata)
    body = INDEX_HTML.replace("<!-- Embed Placeholder -->", render_embed(metadata)).encode("utf-8")
    start_response(str(int(HTTPStatus.OK)), [("Content-Type", "text/html"), ("Content-Length", str(len(body)))])
    return (body,)
