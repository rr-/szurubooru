import re

# pylint: disable=too-many-return-statements
def get_mime_type(content):
    if not content:
        return 'application/octet-stream'

    if content[0:3] in (b'CWS', b'FWS', b'ZWS'):
        return 'application/x-shockwave-flash'

    if content[0:3] == b'\xFF\xD8\xFF':
        return 'image/jpeg'

    if content[0:6] == b'\x89PNG\x0D\x0A':
        return 'image/png'

    if content[0:6] in (b'GIF87a', b'GIF89a'):
        return 'image/gif'

    if content[0:4] == b'\x1A\x45\xDF\xA3':
        return 'video/webm'

    if content[4:12] in (b'ftypisom', b'ftypmp42'):
        return 'video/mp4'

    return 'application/octet-stream'

def get_extension(mime_type):
    extension_map = {
        'application/x-shockwave-flash': 'swf',
        'image/gif': 'gif',
        'image/jpeg': 'jpg',
        'image/png': 'png',
        'video/mp4': 'mp4',
        'video/webm': 'webm',
    }
    return extension_map.get((mime_type or '').strip().lower(), None)

def is_flash(mime_type):
    return mime_type.lower() == 'application/x-shockwave-flash'

def is_video(mime_type):
    return mime_type.lower() in ('application/ogg', 'video/mp4', 'video/webm')

def is_image(mime_type):
    return mime_type.lower() in ('image/jpeg', 'image/png', 'image/gif')

def is_animated_gif(content):
    return get_mime_type(content) == 'image/gif' \
        and len(re.findall(b'\x21\xF9\x04.{4}\x00[\x2C\x21]', content)) > 1
