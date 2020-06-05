from datetime import datetime, timedelta
from typing import Optional

from szurubooru.func import files, util

MAX_MINUTES = 60


def _get_path(checksum: str) -> str:
    return "temporary-uploads/%s.dat" % checksum


def purge_old_uploads() -> None:
    now = datetime.now()
    for file in files.scan("temporary-uploads"):
        file_time = datetime.fromtimestamp(file.stat().st_ctime)
        if now - file_time > timedelta(minutes=MAX_MINUTES):
            files.delete("temporary-uploads/%s" % file.name)


def get(checksum: str) -> Optional[bytes]:
    return files.get("temporary-uploads/%s.dat" % checksum)


def save(content: bytes) -> str:
    checksum = util.get_sha1(content)
    path = _get_path(checksum)
    if not files.has(path):
        files.save(path, content)
    return checksum
