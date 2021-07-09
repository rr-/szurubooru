import os
import os.path
import html
import urllib.parse
import itertools
from typing import Dict, Any, Callable, Tuple, List, Iterable
from http import HTTPStatus
from pathlib import Path

import requests

class HttpStatus:
	def __getattr__(self, name):
		return str(int(getattr(HTTPStatus, name)))

http_status = HttpStatus()

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

def _image_embed(post: Dict[str, Any], skip_twitter_player=False) -> Metadata:
    url = PUBLIC_BASE_URL + post["contentUrl"]

    if post["type"] == "video":
        prefix = "og:video"
        yield "og:image", PUBLIC_BASE_URL + post["thumbnailUrl"]
        if not skip_twitter_player:
            yield "twitter:card", "player"
            yield "twitter:player", PUBLIC_BASE_URL + f"/player/{post['id']}"
            yield "twitter:player:width", str(post["canvasWidth"])
            yield "twitter:player:height", str(post["canvasHeight"])
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

def post_embed(post_id: int, *, skip_twitter_player=False) -> Metadata:
    post = requests.get(BACKEND_BASE_URL + f"/api/post/{post_id}").json()
    yield "og:type", "article"
    yield from _author_embed(post["user"])
    yield "og:title", post["user"]["name"]
    if post["tags"]:
        value = "Tags: " + ", ".join(tag["names"][0] for tag in post["tags"])
        yield "og:description", value
        yield "description", value
    yield from _image_embed(post, skip_twitter_player)

def homepage_embed(server_info: Dict[str, Any], *, skip_twitter_player=False) -> Metadata:
    yield "og:title", server_info["config"]["name"]
    yield "og:type", "website"
    post = server_info["featuredPost"]
    if post is not None:
        yield from _image_embed(post, skip_twitter_player)

def render_embed(metadata: Metadata) -> str:
    out = []
    for k, v in metadata:
        k, v = html.escape(k), html.escape(v)
        out.append(f'<meta property="{k}" content="{v}">')
    return ''.join(out)

def serve_twitter_video_player(start_response, post_id: int):
    r = requests.get(BACKEND_BASE_URL + f"/api/post/{post_id}")
    data = r.json()
    if r.status_code != HTTPStatus.OK:
        start_response(r.status_code, [("Content-Type", "text/html; charset=utf-8")])
        yield f"<h1>{html.escape(data['title'])}</h1><p>{html.escape(data['description'])}</p>".encode("utf-8"),

    start_response(http_status.OK, [("Content-Type", "text/html; charset=utf-8")])
    post = data
    yield b"<!DOCTYPE html><html><head><title>&NegativeMediumSpace;</title>"
    yield b"<style type='text/css'>video { width: 100%; max-width: 600px; height: auto; }</style></head><body>"
    yield b"<video autoplay controls"
    flags = set(post["flags"])
    if "loop" in flags:
        yield b" loop"

    if "sound" not in flags:
        yield b" muted"

    yield f"><source type='{post['mimeType']}' src='{post['contentUrl']}'>Your browser doesn't support HTML5 videos.".encode("utf-8")
    yield b"</video></body></html>"

def application(env: Dict[str, Any], start_response: Callable[[str, Any], Any]) -> Tuple[bytes]:
    def serve_file(path):
        start_response(http_status.OK, [("X-Accel-Redirect", path)])
        return ()

    def serve_without_embed():
        return serve_file("/index.htm")

    method = env["REQUEST_METHOD"]
    if method != "GET":
        start_response(http_status.BAD_REQUEST, [("Content-Type", "text/plain")])
        return (b"Bad request",)

    path = env["PATH_INFO"].lstrip("/")
    path = path.encode("latin-1").decode("utf-8")  # PEP-3333

    if path and (FRONTEND_WEB_ROOT / path).exists():
        return serve_file("/" + path)

    path = "/" + path
    path_components = path.split("/")

    if path_components[1] not in {"post", "user", "", "player"}:
        # serve index.htm like normal
        return serve_without_embed()

    if path_components[1] == "player" and path_components[2]:
        try:
            post_id = int(path_components[2])
        except ValueError:
            pass
        else:
            return serve_twitter_video_player(start_response, post_id)

    server_info = requests.get(BACKEND_BASE_URL + "/api/info").json()
    privileges = server_info["config"]["privileges"]
    # Telegram prefers twitter:card to og:video, so we need to skip the former in order for videos to play inline
    skip_twitter_player = env["HTTP_USER_AGENT"].startswith("TelegramBot")
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
        metadata = post_embed(post_id, skip_twitter_player=skip_twitter_player)

    elif path_components[1] == "":
        metadata = homepage_embed(server_info, skip_twitter_player=skip_twitter_player)

    metadata = itertools.chain(general_embed(server_info), metadata)
    body = INDEX_HTML.replace("<!-- Embed Placeholder -->", render_embed(metadata)).encode("utf-8")
    start_response(http_status.OK, [("Content-Type", "text/html"), ("Content-Length", str(len(body)))])
    return (body,)
