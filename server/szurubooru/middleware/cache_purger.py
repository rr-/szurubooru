from szurubooru.func import cache

class CachePurger(object):
    def process_request(self, request, _response):
        if request.method != 'GET':
            cache.purge()
