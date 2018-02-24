from typing import List
import logging
import json
import shlex
import subprocess
import math
from szurubooru import errors
from szurubooru.func import mime, util


logger = logging.getLogger(__name__)


_SCALE_FIT_FMT = (
    r'scale=iw*max({width}/iw\,{height}/ih):ih*max({width}/iw\,{height}/ih)')


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
        cli = [
            '-i', '{path}',
            '-f', 'image2',
            '-vf', _SCALE_FIT_FMT.format(width=width, height=height),
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
        content = self._execute(cli)
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

    def to_webm(self) -> bytes:
        with util.create_temp_file_path(suffix='.log') as phase_log_path:
            # Pass 1
            self._execute([
                '-i', '{path}',
                '-pass', '1',
                '-passlogfile', phase_log_path,
                '-vcodec', 'libvpx-vp9',
                '-crf', '4',
                '-b:v', '2500K',
                '-acodec', 'libvorbis',
                '-f', 'webm',
                '-y', '/dev/null'
            ])

            # Pass 2
            return self._execute([
                '-i', '{path}',
                '-pass', '2',
                '-passlogfile', phase_log_path,
                '-vcodec', 'libvpx-vp9',
                '-crf', '4',
                '-b:v', '2500K',
                '-acodec', 'libvorbis',
                '-f', 'webm',
                '-'
            ])

    def to_mp4(self) -> bytes:

        with util.create_temp_file_path(suffix='.dat') as mp4_temp_path:

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
                '-i', '{path}',
                '-vcodec', 'libx264',
                '-preset', 'slow',
                '-crf', '22',
                '-b:v', '200K',
                '-profile:v', 'main',
                '-pix_fmt', 'yuv420p',
                '-acodec', 'aac',
                '-f', 'mp4'
            ]

            if altered_dimensions:
                args = args + [
                    '-filter:v', 'scale=\'%d:%d\'' % (width, height)
                ]

            self._execute(args + ['-y', mp4_temp_path])

            with open(mp4_temp_path, 'rb') as mp4_temp:
                return mp4_temp.read()

    def _execute(self, cli: List[str], program: str = 'ffmpeg') -> bytes:
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
                raise errors.ProcessingError(
                    'Error while processing image.\n' + err.decode('utf-8'))
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
