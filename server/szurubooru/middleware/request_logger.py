import logging
from szurubooru import db
from szurubooru.rest import middleware

logger = logging.getLogger(__name__)

@middleware.post_hook
def process_response(ctx):
    logger.info(
        '%s %s (user=%s, queries=%d)',
        ctx.method,
        ctx.url,
        ctx.user.name,
        db.get_query_count())
