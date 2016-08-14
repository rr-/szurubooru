import urllib.request

def download(url):
    assert url
    request = urllib.request.Request(url)
    request.add_header('Referer', url)
    with urllib.request.urlopen(request) as handle:
        return handle.read()
