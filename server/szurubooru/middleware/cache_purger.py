from szurubooru.func import cache
from szurubooru.rest import middleware


@middleware.pre_hook
def process_request(ctx):
    if ctx.method != 'GET':
        cache.purge()
