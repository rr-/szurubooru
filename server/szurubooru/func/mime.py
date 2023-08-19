import re
from typing import Optional


def get_mime_type(content: bytes) -> str:
    if not content:
        return "application/octet-stream"

    if content[0:3] in (b"CWS", b"FWS", b"ZWS"):
        return "application/x-shockwave-flash"

    if content[0:3] == b"\xFF\xD8\xFF":
        return "image/jpeg"

    if content[0:6] == b"\x89PNG\x0D\x0A":
        return "image/png"

    if content[0:6] in (b"GIF87a", b"GIF89a"):
        return "image/gif"

    if content[8:12] == b"WEBP":
        return "image/webp"

    if content[0:2] == b"BM":
        return "image/bmp"

    if content[4:12] in (b"ftypavif", b"ftypavis"):
        return "image/avif"

    if content[4:12] == b"ftypmif1":
        return "image/heif"

    if content[4:12] in (b"ftypheic", b"ftypheix"):
        return "image/heic"

    if content[0:4] == b"\x1A\x45\xDF\xA3":
        return "video/webm"

    if content[4:12] in (b"ftypisom", b"ftypiso5", b"ftypiso6", b"ftypmp42", b"ftypM4V "):
        return "video/mp4"

    if content[4:12] == b"ftypqt  ":
        return "video/quicktime"

    return "application/octet-stream"


def get_extension(mime_type: str) -> Optional[str]:
    extension_map = {
        "application/x-shockwave-flash": "swf",
        "image/gif": "gif",
        "image/jpeg": "jpg",
        "image/png": "png",
        "image/webp": "webp",
        "image/bmp": "bmp",
        "image/avif": "avif",
        "image/heif": "heif",
        "image/heic": "heic",
        "video/mp4": "mp4",
        "video/quicktime": "mov",
        "video/webm": "webm",
        "application/octet-stream": "dat",
    }
    return extension_map.get((mime_type or "").strip().lower(), None)


def is_flash(mime_type: str) -> bool:
    return mime_type.lower() == "application/x-shockwave-flash"


def is_video(mime_type: str) -> bool:
    return mime_type.lower() in (
        "application/ogg",
        "video/mp4",
        "video/quicktime",
        "video/webm",
    )


def is_image(mime_type: str) -> bool:
    return mime_type.lower() in (
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/webp",
        "image/bmp",
        "image/avif",
        "image/heif",
        "image/heic",
    )


def is_animated_gif(content: bytes) -> bool:
    pattern = b"\x21\xF9\x04[\x00-\xFF]{4}\x00[\x2C\x21]"
    return (
        get_mime_type(content) == "image/gif"
        and len(re.findall(pattern, content)) > 1
    )


def is_heif(mime_type: str) -> bool:
    return mime_type.lower() in (
        "image/heif",
        "image/heic",
        "image/avif",
    )
