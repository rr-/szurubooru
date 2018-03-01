import re
from typing import Optional

_APPLICATION_OCTET_STREAM = 'application/octet-stream'
_APPLICATION_OGG = 'application/ogg'
_APPLICATION_SWF = 'application/x-shockwave-flash'
_IMAGE_GIF = 'image/gif'
_IMAGE_JPEG = 'image/jpeg'
_IMAGE_PNG = 'image/png'
_VIDEO_MP4 = 'video/mp4'
_VIDEO_WEBM = 'video/webm'


def get_mime_type(content: bytes) -> str:
    if not content:
        return _APPLICATION_OCTET_STREAM

    if content[0:3] in (b'CWS', b'FWS', b'ZWS'):
        return _APPLICATION_SWF

    if content[0:3] == b'\xFF\xD8\xFF':
        return _IMAGE_JPEG

    if content[0:6] == b'\x89PNG\x0D\x0A':
        return _IMAGE_PNG

    if content[0:6] in (b'GIF87a', b'GIF89a'):
        return _IMAGE_GIF

    if content[0:4] == b'\x1A\x45\xDF\xA3':
        return _VIDEO_WEBM

    if content[4:12] in (b'ftypisom', b'ftypmp42'):
        return _VIDEO_MP4

    return _APPLICATION_OCTET_STREAM


def get_extension(mime_type: str) -> Optional[str]:
    extension_map = {
        _APPLICATION_SWF: 'swf',
        _IMAGE_GIF: 'gif',
        _IMAGE_JPEG: 'jpg',
        _IMAGE_PNG: 'png',
        _VIDEO_MP4: 'mp4',
        _VIDEO_WEBM: 'webm',
        _APPLICATION_OCTET_STREAM: 'dat',
    }
    return extension_map.get((mime_type or '').strip().lower(), None)


def is_flash(mime_type: str) -> bool:
    return mime_type.lower() == _APPLICATION_SWF


def is_video(mime_type: str) -> bool:
    return mime_type.lower() in (_APPLICATION_OGG, _VIDEO_MP4, _VIDEO_WEBM)


def is_image(mime_type: str) -> bool:
    return mime_type.lower() in (_IMAGE_JPEG, _IMAGE_PNG, _IMAGE_GIF)


def is_animated_gif(content: bytes) -> bool:
    pattern = b'\x21\xF9\x04[\x00-\xFF]{4}\x00[\x2C\x21]'
    return (get_mime_type(content) == _IMAGE_GIF
            and len(re.findall(pattern, content)) > 1)
