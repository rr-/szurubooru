from typing import Optional

import re

APPLICATION_SWF = 'application/x-shockwave-flash'
IMAGE_JPEG = 'image/jpeg'
IMAGE_PNG = 'image/png'
IMAGE_GIF = 'image/gif'
VIDEO_WEBM = 'video/webm'
VIDEO_MP4 = 'video/mp4'
APPLICATION_OCTET_STREAM = 'application/octet-stream'


def get_mime_type(content: bytes) -> str:
    if not content:
        return APPLICATION_OCTET_STREAM

    if content[0:3] in (b'CWS', b'FWS', b'ZWS'):
        return APPLICATION_SWF

    if content[0:3] == b'\xFF\xD8\xFF':
        return IMAGE_JPEG

    if content[0:6] == b'\x89PNG\x0D\x0A':
        return IMAGE_PNG

    if content[0:6] in (b'GIF87a', b'GIF89a'):
        return IMAGE_GIF

    if content[0:4] == b'\x1A\x45\xDF\xA3':
        return VIDEO_WEBM

    if content[4:12] in (b'ftypisom', b'ftypmp42'):
        return VIDEO_MP4

    return APPLICATION_OCTET_STREAM


def get_extension(mime_type: str) -> Optional[str]:
    extension_map = {
        APPLICATION_SWF: 'swf',
        IMAGE_GIF: 'gif',
        IMAGE_JPEG: 'jpg',
        IMAGE_PNG: 'png',
        VIDEO_MP4: 'mp4',
        VIDEO_WEBM: 'webm',
        APPLICATION_OCTET_STREAM: 'dat',
    }
    return extension_map.get((mime_type or '').strip().lower(), None)


def is_flash(mime_type: str) -> bool:
    return mime_type.lower() == APPLICATION_SWF


def is_video(mime_type: str) -> bool:
    return mime_type.lower() in ('application/ogg', VIDEO_MP4, VIDEO_WEBM)


def is_image(mime_type: str) -> bool:
    return mime_type.lower() in (IMAGE_JPEG, IMAGE_PNG, IMAGE_GIF)


def is_animated_gif(content: bytes) -> bool:
    pattern = b'\x21\xF9\x04[\x00-\xFF]{4}\x00[\x2C\x21]'
    return get_mime_type(content) == IMAGE_GIF \
           and len(re.findall(pattern, content)) > 1
