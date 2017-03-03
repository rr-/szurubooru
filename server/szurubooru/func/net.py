import urllib.request
from szurubooru import config
from szurubooru import errors


def download(url: str) -> bytes:
    assert url
    request = urllib.request.Request(url)
    if config.config['user_agent']:
        request.add_header('User-Agent', config.config['user_agent'])
    request.add_header('Referer', url)
    try:
        with urllib.request.urlopen(request) as handle:
            return handle.read()
    except Exception as ex:
        raise errors.ProcessingError('Error downloading %s (%s)' % (url, ex))
