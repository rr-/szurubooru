from typing import List
import logging
import json
import shlex
import subprocess
import math
from szurubooru import errors
from szurubooru.func import mime, util


logger = logging.getLogger(__name__)


class Image:
    def __init__(self, content: bytes) -> None:
        self.content = content
        self._reload_info()

    @property
    def width(self) -> int:
        return self.info['streams'][0]['width']

    @property
    def height(self) -> int:
        return self.info['streams'][0]['height']

    @property
    def frames(self) -> int:
        return self.info['streams'][0]['nb_read_frames']

    def resize_fill(self, width: int, height: int) -> None:
        width_greater = self.width > self.height
        width, height = (-1, height) if width_greater else (width, -1)

        cli = [
            '-i', '{path}',
            '-f', 'image2',
            '-filter:v', "scale='{width}:{height}'".format(
                width=width, height=height),
            '-map', '0:v:0',
            '-vframes', '1',
            '-vcodec', 'png',
            '-',
        ]
        if 'duration' in self.info['format'] \
                and self.info['format']['format_name'] != 'swf':
            duration = float(self.info['format']['duration'])
            if duration > 3:
                cli = [
                    '-ss',
                    '%d' % math.floor(duration * 0.3),
                ] + cli
        content = self._execute(cli, ignore_error_if_data=True)
        if not content:
            raise errors.ProcessingError('Error while resizing image.')
        self.content = content
        self._reload_info()

    def to_png(self) -> bytes:
        return self._execute([
            '-i', '{path}',
            '-f', 'image2',
            '-map', '0:v:0',
            '-vframes', '1',
            '-vcodec', 'png',
            '-',
        ])

    def to_jpeg(self) -> bytes:
        return self._execute([
            '-f', 'lavfi',
            '-i', 'color=white:s=%dx%d' % (self.width, self.height),
            '-i', '{path}',
            '-f', 'image2',
            '-filter_complex', 'overlay',
            '-map', '0:v:0',
            '-vframes', '1',
            '-vcodec', 'mjpeg',
            '-',
        ])

    def _execute(
            self,
            cli: List[str],
            program: str = 'ffmpeg',
            ignore_error_if_data: bool = False) -> bytes:
        extension = mime.get_extension(mime.get_mime_type(self.content))
        assert extension
        with util.create_temp_file(suffix='.' + extension) as handle:
            handle.write(self.content)
            handle.flush()
            cli = [program, '-loglevel', '24'] + cli
            cli = [part.format(path=handle.name) for part in cli]
            proc = subprocess.Popen(
                cli,
                stdout=subprocess.PIPE,
                stdin=subprocess.PIPE,
                stderr=subprocess.PIPE)
            out, err = proc.communicate(input=self.content)
            if proc.returncode != 0:
                logger.warning(
                    'Failed to execute ffmpeg command (cli=%r, err=%r)',
                    ' '.join(shlex.quote(arg) for arg in cli),
                    err)
                if ((len(out) > 0 and not ignore_error_if_data)
                        or len(out) == 0):
                    raise errors.ProcessingError(
                        'Error while processing image.\n'
                        + err.decode('utf-8'))
            return out

    def _reload_info(self) -> None:
        self.info = json.loads(self._execute([
            '-i', '{path}',
            '-of', 'json',
            '-select_streams', 'v',
            '-show_format',
            '-show_streams',
        ], program='ffprobe').decode('utf-8'))
        assert 'format' in self.info
        assert 'streams' in self.info
        if len(self.info['streams']) < 1:
            logger.warning('The video contains no video streams.')
            raise errors.ProcessingError(
                'The video contains no video streams.')
