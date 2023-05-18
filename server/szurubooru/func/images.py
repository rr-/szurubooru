import json
import logging
import math
import re
import shlex
import subprocess
from io import BytesIO
from typing import List

import HeifImagePlugin
import pillow_avif
from PIL import Image as PILImage

from szurubooru import errors
from szurubooru.func import mime, util

logger = logging.getLogger(__name__)


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
        return self.info["streams"][0]["width"]

    @property
    def height(self) -> int:
        return self.info["streams"][0]["height"]

    @property
    def frames(self) -> int:
        return self.info["streams"][0]["nb_read_frames"]

    def resize_fill(self, width: int, height: int) -> None:
        width_greater = self.width > self.height
        width, height = (-1, height) if width_greater else (width, -1)

        cli = [
            "-i",
            "{path}",
            "-f",
            "image2",
            "-filter:v",
            "scale='{width}:{height}'".format(width=width, height=height),
            "-map",
            "0:v:0",
            "-vframes",
            "1",
            "-vcodec",
            "png",
            "-",
        ]
        if (
            "duration" in self.info["format"]
            and self.info["format"]["format_name"] != "swf"
        ):
            duration = float(self.info["format"]["duration"])
            if duration > 3:
                cli = [
                    "-ss",
                    "%d" % math.floor(duration * 0.3),
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
                "-quality",
                "85",
                "-sample",
                "1x1",
                "{path}",
            ],
            program="cjpeg",
        )

    def to_webm(self) -> bytes:
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
            ]

            if altered_dimensions:
                args += ["-filter:v", "scale='%d:%d'" % (width, height)]

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
            if program in ["ffmpeg", "ffprobe"]:
                cli = [program, "-loglevel", "32" if get_logs else "24"] + cli
            else:
                cli = [program] + cli
            cli = [part.format(path=handle.name) for part in cli]
            proc = subprocess.Popen(
                cli,
                stdout=subprocess.PIPE,
                stdin=subprocess.DEVNULL,
                stderr=subprocess.PIPE,
            )
            out, err = proc.communicate()
            if proc.returncode != 0:
                logger.warning(
                    "Failed to execute {program} command (cli=%r, err=%r)".format(program=program),
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
        self.info = json.loads(
            self._execute(
                [
                    "-i",
                    "{path}",
                    "-of",
                    "json",
                    "-select_streams",
                    "v",
                    "-show_format",
                    "-show_streams",
                ],
                program="ffprobe",
            ).decode("utf-8")
        )
        assert "format" in self.info
        assert "streams" in self.info
        if len(self.info["streams"]) < 1:
            logger.warning("The video contains no video streams.")
            raise errors.ProcessingError(
                "The video contains no video streams."
            )
