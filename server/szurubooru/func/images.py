import logging
import json
import shlex
import subprocess
import math
from szurubooru import errors
from szurubooru.func import mime, util

logger = logging.getLogger(__name__)

_SCALE_FIT_FMT = \
    r'scale=iw*max({width}/iw\,{height}/ih):ih*max({width}/iw\,{height}/ih)'

class Image(object):
    def __init__(self, content):
        self.content = content
        self._reload_info()

    @property
    def width(self):
        return self.info['streams'][0]['width']

    @property
    def height(self):
        return self.info['streams'][0]['height']

    @property
    def frames(self):
        return self.info['streams'][0]['nb_read_frames']

    def resize_fill(self, width, height):
        cli = [
            '-i', '{path}',
            '-f', 'image2',
            '-vf', _SCALE_FIT_FMT.format(width=width, height=height),
            '-vframes', '1',
            '-vcodec', 'png',
            '-',
        ]
        if 'duration' in self.info['format'] \
                and float(self.info['format']['duration']) > 3 \
                and self.info['format']['format_name'] != 'swf':
            cli = [
                '-ss',
                '%d' % math.floor(float(self.info['format']['duration']) * 0.3),
            ] + cli
        self.content = self._execute(cli)
        assert self.content
        self._reload_info()

    def _to_image(self, codec):
        return self._execute([
            '-i', '{path}',
            '-f', 'image2',
            '-vframes', '1',
            '-vcodec', codec,
            '-',
        ])

    def to_png(self):
        return self._to_image('png')

    def to_jpeg(self):
        return self._to_image('mjpeg')

    def _execute(self, cli, program='ffmpeg'):
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
                with open('/tmp/tmp', 'wb') as handle:
                    handle.write(self.content)

                raise errors.ProcessingError(
                    'Error while processing image.\n' + err.decode('utf-8'))
            return out

    def _reload_info(self):
        self.info = json.loads(self._execute([
            '-i', '{path}',
            '-of', 'json',
            '-select_streams', 'v',
            '-show_format',
            '-show_streams',
        ], program='ffprobe').decode('utf-8'))
        assert 'format' in self.info
        assert 'streams' in self.info
        if len(self.info['streams']) != 1:
            raise errors.ProcessingError('Multiple video streams detected.')
