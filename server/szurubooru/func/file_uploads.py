import datetime
from szurubooru.func import files, util


MAX_MINUTES = 60


def _get_path(checksum):
    return 'temporary-uploads/%s.dat' % checksum


def purge_old_uploads():
    now = datetime.datetime.now()
    for file in files.scan('temporary-uploads'):
        file_time = datetime.datetime.fromtimestamp(file.stat().st_ctime)
        if now - file_time > datetime.timedelta(minutes=MAX_MINUTES):
            files.delete('temporary-uploads/%s' % file.name)


def get(checksum):
    return files.get('temporary-uploads/%s.dat' % checksum)


def save(content):
    checksum = util.get_sha1(content)
    path = _get_path(checksum)
    if not files.has(path):
        files.save(path, content)
    return checksum
