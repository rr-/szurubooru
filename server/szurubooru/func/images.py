import json
import logging
import math
import re
import shlex
import subprocess
from io import BytesIO
from typing import List, Optional
import datetime

import HeifImagePlugin
import pillow_avif
from PIL import Image as PILImage

from szurubooru import errors
from szurubooru.func import mime, util

logger = logging.getLogger(__name__)

# Refer to:
# https://exiftool.org/TagNames/EXIF.html
# https://ffmpeg.org/ffmpeg-filters.html#transpose-1
# https://www.impulseadventure.com/photo/images/orient_flag.gif
ORIENTATION_FILTER = {
    "Horizontal (normal)": "null",
    "Mirror Horizontal": "transpose=clock_flip,transpose=cclock",
    "Rotate 180": "transpose=clock,transpose=clock",
    "Mirror vertical": "transpose=clock_flip,transpose=clock",
    "Mirror horizontal and rotate 270 CW": "transpose=cclock_flip,transpose=clock,transpose=clock",
    "Rotate 90 CW": "transpose=clock",
    "Mirror horizontal and rotate 90 CW": "transpose=clock_flip,transpose=clock,transpose=clock",
    "Rotate 270 CW": "transpose=cclock",
}


ORTHOGONAL_ORIENTATIONS = (
    "Mirror horizontal and rotate 270 CW",
    "Rotate 90 CW",
    "Mirror horizontal and rotate 90 CW",
    "Rotate 270 CW",
)


def convert_heif_to_png(content: bytes) -> bytes:
    img = PILImage.open(BytesIO(content))
    img_byte_arr = BytesIO()
    img.save(img_byte_arr, format="PNG")
    return img_byte_arr.getvalue()


class Image:
    def __init__(self, content: bytes) -> None:
        self.content = content
        self._reload_info()

    @property
    def width(self) -> int:
        if self._is_orthogonal():
            return self.info["ImageHeight"]
        return self.info["ImageWidth"]

    @property
    def height(self) -> int:
        if self._is_orthogonal():
            return self.info["ImageWidth"]
        return self.info["ImageHeight"]

    @property
    def duration(self) -> Optional[datetime.timedelta]:
        try:
            duration_data = self.info["Duration"]
        except KeyError:
            return None

        time_formats = [
            "%H:%M:%S",
            "%H:%M:%S.%f",
            "%M:%S",
            "%M:%S.%f",
            "%S.%f s",
        ]
        for time_format in time_formats:
            try:
                duration = datetime.datetime.strptime(
                    duration_data, time_format).time()
                return datetime.timedelta(
                    hours=duration.hour,
                    minutes=duration.minute,
                    seconds = duration.second,
                    microseconds=duration.microsecond)
            except ValueError:
                pass
        logger.warning("Unexpected time format(duration=%r)", duration_data)
        return None

    def _orientation_filter(self) -> str:
        # This filter should be omitted in ffmpeg>=6.0,
        # where it is automatically applied.
        try:
            return ORIENTATION_FILTER[self.info["Orientation"]]
        except KeyError:
            return "null"

    def _is_orthogonal(self) -> bool:
        try:
            return self.info["Orientation"] in ORTHOGONAL_ORIENTATIONS
        except KeyError:
            return False

    def resize_fill(self, width: int, height: int) -> None:
        width_greater = self.width > self.height
        width, height = (-1, height) if width_greater else (width, -1)

        filters = "{orientation},scale='{width}:{height}'".format(
            orientation=self._orientation_filter(), width=width, height=height)

        cli = [
            "-i",
            "{path}",
            "-f",
            "image2",
            "-filter:v",
            filters,
            "-map",
            "0:v:0",
            "-vframes",
            "1",
            "-vcodec",
            "png",
            "-",
        ]
        duration = self.duration
        if duration is not None and self.info["FileType"] != "SWF":
            total_seconds = duration.total_seconds()
            if total_seconds > 3:
                cli = [
                    "-ss",
                    "%d" % math.floor(total_seconds * 0.3),
                ] + cli
        content = self._execute(cli, ignore_error_if_data=True)
        if not content:
            raise errors.ProcessingError("Error while resizing image.")
        self.content = content
        self._reload_info()

    def to_png(self) -> bytes:
        return self._execute(
            [
                "-i",
                "{path}",
                "-f",
                "image2",
                "-filter:v",
                self._orientation_filter(),
                "-map",
                "0:v:0",
                "-vframes",
                "1",
                "-vcodec",
                "png",
                "-",
            ]
        )

    def to_jpeg(self) -> bytes:
        return self._execute(
            [
                "-f",
                "lavfi",
                "-i",
                "color=white:s=%dx%d" % (self.width, self.height),
                "-i",
                "{path}",
                "-f",
                "image2",
                "-filter_complex",
                "overlay," + self._orientation_filter(),
                "-map",
                "0:v:0",
                "-vframes",
                "1",
                "-vcodec",
                "mjpeg",
                "-",
            ]
        )

    def to_webm(self) -> bytes:
        filters = self._orientation_filter()
        with util.create_temp_file_path(suffix=".log") as phase_log_path:
            # Pass 1
            self._execute(
                [
                    "-i",
                    "{path}",
                    "-pass",
                    "1",
                    "-passlogfile",
                    phase_log_path,
                    "-filter:v",
                    filters,
                    "-vcodec",
                    "libvpx-vp9",
                    "-crf",
                    "4",
                    "-b:v",
                    "2500K",
                    "-acodec",
                    "libvorbis",
                    "-f",
                    "webm",
                    "-y",
                    "/dev/null",
                ]
            )

            # Pass 2
            return self._execute(
                [
                    "-i",
                    "{path}",
                    "-pass",
                    "2",
                    "-passlogfile",
                    phase_log_path,
                    "-filter:v",
                    filters,
                    "-vcodec",
                    "libvpx-vp9",
                    "-crf",
                    "4",
                    "-b:v",
                    "2500K",
                    "-acodec",
                    "libvorbis",
                    "-f",
                    "webm",
                    "-",
                ]
            )

    def to_mp4(self) -> bytes:
        with util.create_temp_file_path(suffix=".dat") as mp4_temp_path:
            width = self.width
            height = self.height
            altered_dimensions = False

            if self.width % 2 != 0:
                width = self.width - 1
                altered_dimensions = True

            if self.height % 2 != 0:
                height = self.height - 1
                altered_dimensions = True

            filters = self._orientation_filter()
            if altered_dimensions:
                filters += ",scale='%d:%d'" % (width, height)

            args = [
                "-i",
                "{path}",
                "-vcodec",
                "libx264",
                "-preset",
                "slow",
                "-crf",
                "22",
                "-b:v",
                "200K",
                "-profile:v",
                "main",
                "-pix_fmt",
                "yuv420p",
                "-acodec",
                "aac",
                "-f",
                "mp4",
                "-filter:v",
                filters,
            ]

            self._execute(args + ["-y", mp4_temp_path])

            with open(mp4_temp_path, "rb") as mp4_temp:
                return mp4_temp.read()

    def check_for_sound(self) -> bool:
        audioinfo = json.loads(
            self._execute(
                [
                    "-i",
                    "{path}",
                    "-of",
                    "json",
                    "-select_streams",
                    "a",
                    "-show_streams",
                ],
                program="ffprobe",
            ).decode("utf-8")
        )
        assert "streams" in audioinfo
        if len(audioinfo["streams"]) < 1:
            return False

        log = self._execute(
            [
                "-hide_banner",
                "-progress",
                "-",
                "-i",
                "{path}",
                "-af",
                "volumedetect",
                "-max_muxing_queue_size",
                "99999",
                "-vn",
                "-sn",
                "-f",
                "null",
                "-y",
                "/dev/null",
            ],
            get_logs=True,
        ).decode("utf-8", errors="replace")
        log_match = re.search(r".*volumedetect.*mean_volume: (.*) dB", log)
        if not log_match or not log_match.groups():
            raise errors.ProcessingError(
                "A problem occured when trying to check for audio"
            )
        meanvol = float(log_match.groups()[0])

        # -91.0 dB is the minimum for 16-bit audio, assume sound if > -80.0 dB
        return meanvol > -80.0

    def _execute(
        self,
        cli: List[str],
        program: str = "ffmpeg",
        ignore_error_if_data: bool = False,
        get_logs: bool = False,
    ) -> bytes:
        mime_type = mime.get_mime_type(self.content)
        if mime.is_heif(mime_type):
            # FFmpeg does not support HEIF.
            # https://trac.ffmpeg.org/ticket/6521
            self.content = convert_heif_to_png(self.content)
        extension = mime.get_extension(mime_type)
        assert extension
        with util.create_temp_file(suffix="." + extension) as handle:
            handle.write(self.content)
            handle.flush()
            cli = [part.format(path=handle.name) for part in cli]
            if program in ("ffmpeg", "ffprobe"):
                cli = ["-loglevel", "32" if get_logs else "24"] + cli
            cli = [program] + cli
            proc = subprocess.Popen(
                cli,
                stdout=subprocess.PIPE,
                stdin=subprocess.DEVNULL,
                stderr=subprocess.PIPE,
            )
            out, err = proc.communicate()
            if proc.returncode != 0:
                logger.warning(
                    "Failed to execute command (cli=%r, err=%r)",
                    " ".join(shlex.quote(arg) for arg in cli),
                    err,
                )
                if (len(out) > 0 and not ignore_error_if_data) or len(
                    out
                ) == 0:
                    raise errors.ProcessingError(
                        "Error while processing image.\n" + err.decode("utf-8")
                    )
            return err if get_logs else out

    def _reload_info(self) -> None:
        exiftool_data = json.loads(
            self._execute(
                [
                    "{path}",
                    "-json",
                ],
                program="exiftool",
            ).decode("utf-8")
        )

        if len(exiftool_data) != 1:
            logger.warning("Unexpected output from exiftool")

        self.info = exiftool_data[0]

        if "Error" in self.info:
            raise errors.ProcessingError(
                "Error in metadata:" + str(self.info["Error"]))

        if "Warning" in self.info:
            raise errors.ProcessingError(
                "Warning in metadata:" + str(self.info["Warning"]))