import json
import logging
from datetime import datetime
from subprocess import PIPE, Popen
from typing import Optional

from exif import Image

logger = logging.getLogger(__name__)


def resolve_image_date_taken(content: bytes) -> Optional[datetime]:
    try:
        img = Image(content)
    except Exception:
        logger.warning("Error reading image with exif library!")
        return None

    if img.has_exif:
        if "datetime" in img.list_all():
            resolved = img.datetime
        elif "datetime_original" in img.list_all():
            resolved = img.datetime_original
        else:
            return None

        return datetime.strptime(resolved, "%Y:%m:%d %H:%M:%S")
    else:
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
