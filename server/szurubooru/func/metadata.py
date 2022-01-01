import json
import logging
from datetime import datetime
from subprocess import PIPE, Popen
from typing import Optional, Tuple, Union

from exif import Image

logger = logging.getLogger(__name__)


BASE_FFPROBE_COMMAND = [
    "ffprobe",
    "-loglevel",
    "8",
    "-print_format",
    "json",
    "-show_format",
    "-show_streams",
]


def _open_image(content: bytes) -> Image:
    tags = Image(content)

    if not tags.has_exif or not tags.list_all():
        raise Exception

    return tags


def _run_ffprobe(content: Union[bytes, str]) -> Image:
    if isinstance(content, bytes):
        proc = Popen(
            BASE_FFPROBE_COMMAND + ["-"],
            stdin=PIPE,
            stdout=PIPE,
            stderr=PIPE,
        )

        output = proc.communicate(input=content)[0]
    else:
        proc = Popen(
            BASE_FFPROBE_COMMAND + [content],
            stdout=PIPE,
            stderr=PIPE,
        )

        output = proc.communicate()[0]

    return json.loads(output)["format"]["tags"]


def resolve_image_date_taken(
    content: Union[bytes, Image]
) -> Optional[datetime]:
    try:
        if isinstance(content, Image):
            tags = content
        else:
            tags = _open_image(content)

        resolved = None

        for option in ("datetime", "datetime_original"):
            if option in tags.list_all():
                resolved = tags[option]
                break

        if not resolved:
            raise Exception
    except Exception:
        return None
    else:
        return datetime.strptime(resolved, "%Y:%m:%d %H:%M:%S")


def resolve_video_date_taken(
    content: Union[bytes, str, dict]
) -> Optional[datetime]:
    try:
        if isinstance(content, dict):
            tags = content
        else:
            tags = _run_ffprobe(content)

        creation_time = tags["creation_time"]
    except Exception:
        return None
    else:
        return datetime.fromisoformat(creation_time.rstrip("Z"))


def resolve_image_camera(content: Union[bytes, Image]) -> Optional[str]:
    try:
        if isinstance(content, Image):
            tags = content
        else:
            tags = _open_image(content)

        camera_string = []

        for option in ("make", "model"):
            if option in tags.list_all():
                camera_string.append(tags[option])

        if not camera_string:
            raise Exception
    except Exception:
        return None
    else:
        return " ".join(camera_string)


def resolve_video_camera(content: Union[bytes, str, dict]) -> Optional[str]:
    try:
        if isinstance(content, dict):
            tags = content
        else:
            tags = _run_ffprobe(content)

        # List of tuples where only one value can be valid
        option_tuples = (
            ("manufacturer", "com.android.manufacturer"),
            ("model", "com.android.model"),
        )

        camera_string = []

        for option_tuple in option_tuples:
            for option in option_tuple:
                if option in tags:
                    camera_string.append(tags[option])
                    break

        if not camera_string:
            raise Exception
    except Exception:
        return None
    else:
        return " ".join(camera_string)


def resolve_real_image_dimensions(
    content: Union[bytes, Image]
) -> Optional[Tuple[int, int]]:
    try:
        if isinstance(content, Image):
            tags = content
        else:
            tags = _open_image(content)

        orig_w = tags["pixel_x_dimension"]
        orig_h = tags["pixel_y_dimension"]

        # read: https://jdhao.github.io/2019/07/31/image_rotation_exif_info/
        # 8, 6, 5, 7 are orientation values where the image is rotated 90
        # degrees CW or CCW. in this case, we swap the two dimensions.
        if tags["orientation"] in (8, 6, 5, 7):
            dimensions = (orig_h, orig_w)
        else:
            dimensions = (orig_w, orig_h)
    except Exception:
        return (0, 0)
    else:
        return dimensions


def resolve_video_dimensions(
    content: Union[bytes, str, dict]
) -> Optional[Tuple[int, int]]:
    try:
        if isinstance(content, dict):
            tags = content
        else:
            tags = _run_ffprobe(content)

        stream = tags["format"]["streams"][0]
        dimensions = (stream["width"], stream["height"])
    except Exception:
        return (0, 0)
    else:
        return dimensions
