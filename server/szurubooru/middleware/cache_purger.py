from szurubooru import rest
from szurubooru.func import cache
from szurubooru.rest import middleware


@middleware.pre_hook
def process_request(ctx: rest.Context) -> None:
    if ctx.method != "GET":
        cache.purge()
