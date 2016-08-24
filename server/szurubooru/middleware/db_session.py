from szurubooru import db
from szurubooru.rest import middleware


@middleware.pre_hook
def _process_request(ctx):
    ctx.session = db.session()


@middleware.post_hook
def _process_response(_ctx):
    db.session.remove()
