import json
from datetime import datetime
from subprocess import PIPE, Popen
from typing import Optional

from exif import Image


def resolve_image_date_taken(content: bytes) -> Optional[datetime]:
    img = Image(content)

    if img.has_exif and "datetime" in img.list_all():
        return datetime.strptime(img.datetime, "%Y:%m:%d %H:%M:%S")
    return None


def resolve_video_date_taken(content: bytes) -> Optional[datetime]:
    proc = Popen(
        [
            "ffprobe",
            "-loglevel",
            "8",
            "-print_format",
            "json",
            "-show_format",
            "-",
        ],
        stdin=PIPE,
        stdout=PIPE,
        stderr=PIPE,
    )

    output = proc.communicate(input=content)[0]
    json_output = json.loads(output)

    try:
        creation_time = json_output["format"]["tags"]["creation_time"]
        return datetime.fromisoformat(creation_time.rstrip("Z"))
    except Exception:
        return None
