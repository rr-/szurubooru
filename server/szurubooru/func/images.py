import subprocess
from szurubooru import errors

_SCALE_FIT_FMT = \
    r'scale=iw*max({width}/iw\,{height}/ih):ih*max({width}/iw\,{height}/ih)'

class Image(object):
    def __init__(self, content):
        self.content = content

    def resize_fill(self, width, height):
        self.content = self._execute([
            '-i', '-',
            '-f', 'image2',
            '-vf', _SCALE_FIT_FMT.format(width=width, height=height),
            '-vframes', '1',
            '-vcodec', 'png',
            '-',
        ])

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

    def _execute(self, cli):
        proc = subprocess.Popen(
            ['ffmpeg', '-loglevel', '24'] + cli,
            stdout=subprocess.PIPE,
            stdin=subprocess.PIPE,
            stderr=subprocess.PIPE)
        out, err = proc.communicate(input=self.content)
        if proc.returncode != 0:
            raise errors.ProcessingError(
                'Error while processing image.\n' + err.decode('utf-8'))
        return out
