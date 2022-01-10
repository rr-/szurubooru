import json
import logging
import math
import re
import shlex
from datetime import datetime
from io import BytesIO
from subprocess import PIPE, Popen
from typing import List

from exif import Image as EXIFImage
from PIL import Image as PILImage

from szurubooru import errors
from szurubooru.func import mime, util

logger = logging.getLogger(__name__)


# Refer to: https://www.impulseadventure.com/photo/images/orient_flag.gif
# and https://ffmpeg.org/ffmpeg-filters.html#transpose-1
EXIF_ORIENTATION_TRANSPOSES = (
    "transpose=clock_flip,transpose=cclock",  # Starts from orientation 2
    "transpose=clock,transpose=clock",
    "transpose=clock_flip,transpose=clock",
    "transpose=cclock_flip,transpose=clock,transpose=clock",
    "transpose=clock",
    "transpose=clock_flip,transpose=clock,transpose=clock",
    "transpose=cclock",
)


def convert_heif_to_png(content: bytes) -> bytes:
    img = PILImage.open(BytesIO(content))
    img_byte_arr = BytesIO()
    img.save(img_byte_arr, format="PNG")
    return img_byte_arr.getvalue()


def _execute_ffmpeg(
    content: bytes,
    cli: List[str],
    program: str = "ffmpeg",
    ignore_error_if_data: bool = False,
    get_logs: bool = False,
) -> bytes:
    mime_type = mime.get_mime_type(content)
    if mime.is_heif(mime_type):
        # FFmpeg does not support HEIF.
        # https://trac.ffmpeg.org/ticket/6521
        content = convert_heif_to_png(content)

    cli = [program, "-loglevel", "32" if get_logs else "24"] + cli

    proc = Popen(cli, stdout=PIPE, stdin=PIPE, stderr=PIPE)
    out, err = proc.communicate(input=content)

    if proc.returncode != 0:
        args = " ".join(shlex.quote(arg) for arg in cli)
        logger.warning(
            f"Failed to execute {program} command (cli={args}, err={err})"
        )
        if (len(out) > 0 and not ignore_error_if_data) or len(out) == 0:
            raise errors.ProcessingError(
                "Error while processing media.\n" + err.decode("utf-8")
            )
    return err if get_logs else out


class Image:
    def __init__(self, content: bytes) -> None:
        self.content = content

        self.width = None
        self.height = None
        self.orientation = 1
        self.date_taken = None
        self.camera = None

        self._reload_info()

    def to_thumbnail(self, width: int, height: int) -> bytes:
        width_greater = self.width > self.height
        width, height = (-1, height) if width_greater else (width, -1)

        cli = ["-i", "-"]
        self._add_orientation_filters(cli, f"scale='{width}:{height}'")
        cli += ["-f", "image2", "-vframes", "1", "-vcodec", "png", "-"]

        content = _execute_ffmpeg(self.content, cli, ignore_error_if_data=True)

        if not content:
            raise errors.ProcessingError(
                "Error while creating thumbnail from image."
            )

        return content

    def to_png(self) -> bytes:
        cli = ["-i", "-"]
        self._add_orientation_filters(cli)
        cli += ["-f", "image2", "-vframes", "1", "-vcodec", "mjpeg", "-"]

        return _execute_ffmpeg(self.content, cli, ignore_error_if_data=True)

    def to_jpeg(self) -> bytes:
        cli = [
            "-f",
            "lavfi",
            "-i",
            f"color=white:s={self.width}x{self.height}",
            "-i",
            "-",
        ]
        self._add_orientation_filters(cli)
        cli += [
            "-f",
            "image2",
            "-filter_complex",
            "overlay",
            "-vframes",
            "1",
            "-vcodec",
            "mjpeg",
            "-",
        ]

        return _execute_ffmpeg(self.content, cli, ignore_error_if_data=True)

    def check_for_sound(self) -> bool:
        return False

    def _add_orientation_filters(
        self, cmd: List[str], extra_filters=""
    ) -> None:
        if not extra_filters and self.orientation == 1:
            return

        transpose = EXIF_ORIENTATION_TRANSPOSES[self.orientation - 2]
        if extra_filters:
            transpose += "," + extra_filters

        cmd.append("-vf")
        cmd.append(transpose)

    def _extract_from_exif(self) -> None:
        tags = EXIFImage(self.content)

        if tags.has_exif and tags.list_all():
            self.orientation = tags["orientation"]

            # 5, 6, 7, and 8 are orientation values where the image is rotated
            # 90 degrees CW or CCW.
            if self.orientation in (5, 6, 7, 8):
                self.width = tags["pixel_y_dimension"]
                self.height = tags["pixel_x_dimension"]
            else:
                self.width = tags["pixel_x_dimension"]
                self.height = tags["pixel_y_dimension"]

            for option in ("datetime", "datetime_original"):
                if option in tags.list_all():
                    self.date_taken = datetime.strptime(
                        tags[option],
                        "%Y:%m:%d %H:%M:%S",
                    )

            camera_string = []

            for option in ("make", "model"):
                if option in tags.list_all():
                    camera_string.append(tags[option])

            if camera_string:
                self.camera = " ".join(camera_string)
        else:
            raise Exception

    def _extract_using_ffmpeg(self) -> None:
        cmd = ["-i", "-", "-print_format", "json", "-show_streams"]
        info = json.loads(
            _execute_ffmpeg(self.content, cmd, program="ffprobe").decode(
                "utf-8"
            )
        )

        assert "streams" in info
        if len(info["streams"]) > 0:
            self.width = info["streams"][0]["width"]
            self.height = info["streams"][0]["height"]

    def _reload_info(self) -> None:
        try:
            self._extract_from_exif()
        except Exception:
            self._extract_using_ffmpeg()

        assert self.width > 0
        assert self.height > 0
        if (not self.width) or (not self.height):
            logger.warning("Error processing this image.")
            raise errors.ProcessingError("Error processing this image.")


class Video:
    def __init__(self, content: bytes) -> None:
        self.content = content

        self.width = None
        self.height = None
        self.date_taken = None
        self.camera = None
        self.frames = 0
        self.duration = 0

        self._reload_info()

    def to_thumbnail(self, width: int, height: int) -> bytes:
        width_greater = self.width > self.height
        width, height = (-1, height) if width_greater else (width, -1)

        cli = []

        if float(self.duration) > 3.0:
            cli += ["-ss", math.floor(self.duration * 0.3)]

        cli += [
            "-i",
            "-",
            "-f",
            "image2",
            "-vf",
            f"scale={width}:{height}",
            "-vframes",
            "1",
            "-vcodec",
            "mjpeg",
            "-",
        ]

        content = _execute_ffmpeg(self.content, cli, ignore_error_if_data=True)

        if not content:
            raise errors.ProcessingError(
                "Error while creating thumbnail from video."
            )

        return content

    def to_webm(self) -> bytes:
        with util.create_temp_file_path(suffix=".log") as phase_log_path:
            # Pass 1
            _execute_ffmpeg(
                self.content,
                [
                    "-i",
                    "-",
                    "-pass",
                    "1",
                    "-passlogfile",
                    phase_log_path,
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
                ],
            )

            # Pass 2
            return _execute_ffmpeg(
                self.content,
                [
                    "-i",
                    "-",
                    "-pass",
                    "2",
                    "-passlogfile",
                    phase_log_path,
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
                ],
            )

    def to_mp4(self) -> bytes:
        # I would like to know why making ffmpeg output to a tempfile is
        # necessary here and not when converting webms for example
        with util.create_temp_file_path(suffix=".dat") as mp4_temp_path:
            _execute_ffmpeg(
                self.content,
                [
                    "-i",
                    "-",
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
                    "-y",
                    mp4_temp_path,
                ],
            )

            with open(mp4_temp_path, "rb") as data:
                return data.read()

    def check_for_sound(self) -> bool:
        audioinfo = json.loads(
            _execute_ffmpeg(
                self.content,
                [
                    "-i",
                    "-",
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

        log = _execute_ffmpeg(
            self.content,
            [
                "-hide_banner",
                "-progress",
                "-",
                "-i",
                "-",
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

    def _reload_info(self):
        cmd = [
            "-i",
            "-",
            "-print_format",
            "json",
            "-show_streams",
            "-show_format",
        ]

        info = json.loads(
            _execute_ffmpeg(
                self.content,
                cmd,
                program="ffprobe",
            ).decode("utf-8")
        )

        assert "streams" in info
        if len(info["streams"]) < 1:
            logger.warning("This video contains no video streams.")
            raise errors.ProcessingError(
                "The video contains no video streams."
            )

        self.width = info["streams"][0]["width"]
        self.height = info["streams"][0]["height"]

        assert "format" in info
        assert "tags" in info["format"]

        if "creation_time" in info["format"]["tags"]:
            self.date_taken = info["format"]["tags"]["creation_time"]

        # List of tuples where only one value can be valid
        option_tuples = (
            ("manufacturer", "com.android.manufacturer"),
            ("model", "com.android.model"),
        )

        camera_string = []

        for option_tuple in option_tuples:
            for option in option_tuple:
                if option in info["format"]["tags"]:
                    camera_string.append(info["format"]["tags"][option])
                    break

        if camera_string:
            self.camera = " ".join(camera_string)
