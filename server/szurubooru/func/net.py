import json
import logging
import os
import urllib.error
import urllib.request
from tempfile import NamedTemporaryFile
from threading import Thread
from typing import Any, Dict, List

from youtube_dl import YoutubeDL
from youtube_dl.utils import YoutubeDLError

from szurubooru import config, errors
from szurubooru.func import mime, util

logger = logging.getLogger(__name__)


def download(url: str, use_video_downloader: bool = False) -> bytes:
    assert url
    request = urllib.request.Request(url)
    if config.config["user_agent"]:
        request.add_header("User-Agent", config.config["user_agent"])
    request.add_header("Referer", url)
    try:
        with urllib.request.urlopen(request) as handle:
            content = handle.read()
    except Exception as ex:
        raise errors.ProcessingError("Error downloading %s (%s)" % (url, ex))
    if (
        use_video_downloader
        and mime.get_mime_type(content) == "application/octet-stream"
    ):
        return _youtube_dl_wrapper(url)
    return content


def _youtube_dl_wrapper(url: str) -> bytes:
    outpath = os.path.join(
        config.config["data_dir"],
        "temporary-uploads",
        "youtubedl-" + util.get_sha1(url)[0:8] + ".dat",
    )
    options = {
        "ignoreerrors": False,
        "format": "best[ext=webm]/best[ext=mp4]/best[ext=flv]",
        "logger": logger,
        "max_filesize": config.config["max_dl_filesize"],
        "max_downloads": 1,
        "outtmpl": outpath,
    }
    try:
        with YoutubeDL(options) as ydl:
            ydl.extract_info(url, download=True)
        with open(outpath, "rb") as f:
            return f.read()
    except YoutubeDLError as ex:
        raise errors.ThirdPartyError(
            "Error downloading video %s (%s)" % (url, ex)
        )
    except FileNotFoundError:
        raise errors.ThirdPartyError(
            "Error downloading video %s (file could not be saved)" % (url)
        )


def post_to_webhooks(payload: Dict[str, Any]) -> List[Thread]:
    threads = [
        Thread(target=_post_to_webhook, args=(webhook, payload))
        for webhook in (config.config["webhooks"] or [])
    ]
    for thread in threads:
        thread.daemon = False
        thread.start()
    return threads


def _post_to_webhook(webhook: str, payload: Dict[str, Any]) -> None:
    req = urllib.request.Request(webhook)
    req.data = json.dumps(
        payload,
        default=lambda x: x.isoformat("T") + "Z",
    ).encode("utf-8")
    req.add_header("Content-Type", "application/json")
    try:
        res = urllib.request.urlopen(req)
        if not 200 <= res.status <= 299:
            logger.warning(
                f"Webhook {webhook} returned {res.status} {res.reason}"
            )
        return res.status
    except urllib.error.URLError as e:
        logger.warning(f"Unable to call webhook {webhook}: {str(e)}")
        return 400
