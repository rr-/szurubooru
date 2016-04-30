import json
import subprocess
from szurubooru import errors

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
        self.content = self._execute([
            '-i', '-',
            '-f', 'image2',
            '-vf', _SCALE_FIT_FMT.format(width=width, height=height),
            '-vframes', '1',
            '-vcodec', 'png',
            '-',
        ])
        self._reload_info()

    def to_png(self):
        return self._execute([
            '-i', '-',
            '-f', 'image2',
            '-vframes', '1',
            '-vcodec', 'png',
            '-',
        ])

    def to_jpeg(self):
        return self._execute([
            '-i', '-',
            '-f', 'image2',
            '-vframes', '1',
            '-vcodec', 'mjpeg',
            '-',
        ])

    def _execute(self, cli, program='ffmpeg'):
        proc = subprocess.Popen(
            [program, '-loglevel', '24'] + cli,
            stdout=subprocess.PIPE,
            stdin=subprocess.PIPE,
            stderr=subprocess.PIPE)
        out, err = proc.communicate(input=self.content)
        if proc.returncode != 0:
            raise errors.ProcessingError(
                'Error while processing image.\n' + err.decode('utf-8'))
        return out

    def _reload_info(self):
        self.info = json.loads(self._execute([
            '-of', 'json',
            '-select_streams', 'v',
            '-show_streams',
            '-count_frames',
            '-i', '-',
        ], program='ffprobe').decode('utf-8'))
        assert 'streams' in self.info
        if len(self.info['streams']) != 1:
            raise errors.ProcessingError('Multiple video streams detected.')
