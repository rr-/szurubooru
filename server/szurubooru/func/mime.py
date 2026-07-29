import re
from typing import Optional
import magic
import mimetypes

mimetypes.add_type("application/octet-stream", ".dat")
mimetypes.add_type("application/ogg", ".ogg")
mimetypes.add_type("application/x-shockwave-flash", ".swf")
mimetypes.add_type("image/avif", ".avif")
mimetypes.add_type("image/bmp", ".bmp")
mimetypes.add_type("image/gif", ".gif")
mimetypes.add_type("image/heic", ".heic")
mimetypes.add_type("image/heif", ".heif")
mimetypes.add_type("image/jpeg", ".jpg")
mimetypes.add_type("image/png", ".png")
mimetypes.add_type("image/webp", ".webp")
mimetypes.add_type("video/m4v", ".mp4")             # Apple MV4 Container
mimetypes.add_type("video/x-m4v", ".mp4")           # Apple MV4 Container
mimetypes.add_type("video/mp4", ".mp4")
mimetypes.add_type("video/quicktime", ".mov")
mimetypes.add_type("video/webm", ".webm")

EBML_MAGIC = b"\x1a\x45\xdf\xa3"

def get_mime_type(content: bytes) -> str:
    if not content:
        return "application/octet-stream"

    # WebM are inside a EBML and share a common magic with a lot of files.
    # We have to inspect deeper to see if it is a webm specifically.
    if content[:4] == EBML_MAGIC:
        idx = content[:1024].find(b"\x42\x82")   # DocType element ID
        if idx != -1 and b"webm" in content[idx + 2 : idx + 14]:
            return "video/webm"

    return magic.from_buffer(content, mime=True)


def get_extension(mime_type: str) -> Optional[str]:
    if mime_type == "application/octet-stream":
        return "dat"
    guess = mimetypes.guess_extension(mime_type or "", strict=False)
    if guess is None:
        return None
    return guess[1:]


def is_flash(mime_type: str) -> bool:
    return mime_type.lower() == "application/x-shockwave-flash"


def is_video(mime_type: str) -> bool:
    return mime_type.lower() in (
        "application/ogg",
        "video/m4v",
        "video/x-m4v",
        "video/mp4",
        "video/quicktime",
        "video/webm",
    )


def is_image(mime_type: str) -> bool:
    return mime_type.lower() in (
        "image/avif",
        "image/bmp",
        "image/gif",
        "image/heic",
        "image/heif",
        "image/jpeg",
        "image/png",
        "image/webp",
    )


def is_animated_gif(content: bytes) -> bool:
    pattern = b"\x21\xF9\x04[\x00-\xFF]{4}\x00[\x2C\x21]"
    return (
        get_mime_type(content) == "image/gif"
        and len(re.findall(pattern, content)) > 1
    )


def is_heif(mime_type: str) -> bool:
    return mime_type.lower() in (
        "image/avif",
        "image/heic",
        "image/heif",
    )
