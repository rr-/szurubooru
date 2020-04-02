import logging
import urllib.request
import os
from tempfile import NamedTemporaryFile
from szurubooru import config, errors
from szurubooru.func import mime, util
from youtube_dl import YoutubeDL
from youtube_dl.utils import YoutubeDLError


logger = logging.getLogger(__name__)


def download(url: str, use_video_downloader: bool = False) -> bytes:
    assert url
    request = urllib.request.Request(url)
    if config.config['user_agent']:
        request.add_header('User-Agent', config.config['user_agent'])
    request.add_header('Referer', url)
    try:
        with urllib.request.urlopen(request) as handle:
            content = handle.read()
    except Exception as ex:
        raise errors.ProcessingError('Error downloading %s (%s)' % (url, ex))
    if (use_video_downloader and
            mime.get_mime_type(content) == 'application/octet-stream'):
        return _youtube_dl_wrapper(url)
    return content


def _youtube_dl_wrapper(url: str) -> bytes:
    options = {
        'quiet': True,
        'format': 'webm/mp4',
        'logger': logger,
        'noplaylist': True,
        'outtmpl': os.path.join(
            config.config['data_dir'],
            'temporary-uploads',
            'youtubedl-' + util.get_sha1(url)[0:8] + '.%(ext)s'),
    }
    with YoutubeDL(options) as ydl:
        try:
            ydl_info = ydl.extract_info(url, download=True)
            ydl_filename = ydl.prepare_filename(ydl_info)
        except YoutubeDLError as ex:
            raise errors.ThirdPartyError(
                'Error downloading video %s (%s)' % (url, ex))
    with open(ydl_filename, 'rb') as f:
        return f.read()
