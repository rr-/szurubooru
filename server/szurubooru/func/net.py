import json
import logging
import subprocess
from threading import Thread
from typing import Any, Dict, List

import requests

from szurubooru import config, errors
from szurubooru.func import mime

logger = logging.getLogger(__name__)
_dl_chunk_size = 2 ** 15


class DownloadError(errors.ProcessingError):
    pass


class DownloadTooLargeError(DownloadError):
    pass


def download(url: str, use_video_downloader: bool = False) -> bytes:
    assert url
    youtube_dl_error = None
    if use_video_downloader:
        try:
            url = _get_youtube_dl_content_url(url) or url
        except errors.ThirdPartyError as ex:
            youtube_dl_error = ex

    headers = {'Referer': url}
    if config.config["user_agent"]:
        headers["User-Agent"] = config.config["user_agent"]

    proxies = {}
    if config.config["proxy"]:
        proxies["http"] = proxies["https"] = config.config["proxy"]

    content_buffer = []
    length_tally = 0
    try:
        response = requests.get(url, headers=headers, proxies=proxies, stream=True)
        response.raise_for_status()
        for chunk in response.iter_content(
            chunk_size=_dl_chunk_size,
            decode_unicode=False,
        ):
            length_tally += len(chunk)
            if length_tally > config.config["max_dl_filesize"]:
                raise DownloadTooLargeError(
                    "Download target exceeds maximum. (%d)"
                    % (config.config["max_dl_filesize"]),
                    extra_fields={"URL": url},
                )
            content_buffer.append(chunk)
    except requests.HTTPError as ex:
        raise DownloadError(
            "Download target returned HTTP %d. (%s)" % (ex.code, ex.reason),
            extra_fields={"URL": url},
        ) from ex
    except requests.ConnectionError as ex:
        raise DownloadError(
            "General error connecting to server",
            extra_fields={"URL": url},
        ) from ex

    if (
        youtube_dl_error
        and mime.get_mime_type(content_buffer) == "application/octet-stream"
    ):
        raise youtube_dl_error

    return b"".join(content_buffer)


def _get_youtube_dl_content_url(url: str) -> str:
    cmd = ["yt-dlp", "--format", "best", "--no-playlist"]
    if config.config["user_agent"]:
        cmd.extend(["--user-agent", config.config["user_agent"]])
    if config.config["proxy"]:
        cmd.extend(["--proxy", config.config["proxy"]])
    cmd.extend(["--get-url", url])
    try:
        return (
            subprocess.run(cmd, text=True, capture_output=True, check=True)
            .stdout.split("\n")[0]
            .strip()
        )
    except subprocess.CalledProcessError:
        raise errors.ThirdPartyError(
            "Could not extract content location from URL.",
            extra_fields={"URL": url},
        ) from None


def post_to_webhooks(payload: Dict[str, Any]) -> List[Thread]:
    threads = [
        Thread(target=_post_to_webhook, args=(webhook, payload), daemon=False)
        for webhook in (config.config["webhooks"] or [])
    ]
    for thread in threads:
        thread.start()
    return threads


def _post_to_webhook(webhook: str, payload: Dict[str, Any]) -> int:
    data = json.dumps(
        payload,
        default=lambda x: x.isoformat("T") + "Z",
    ).encode("utf-8")
    headers = {"Content-Type": "application/json"}
    if config.config["user_agent"]:
        headers["User-Agent"] = config.config["user_agent"]
    proxies = {}
    if config.config["proxy"] and config.config["proxy_webhook"]:
        proxies["http"] = proxies["https"] = config.config["proxy"]
    try:
        res = requests.get(webhook, data=data, proxies=proxies, headers=headers)
        if res.status_code not in range(200, 300):
            logger.warning(
                f"Webhook {webhook} returned {res.status} {res.reason}"
            )
        return res.status_code
    except requests.RequestException as ex:
        logger.warning(f"Unable to call webhook {webhook}: {ex}")
        return 400
