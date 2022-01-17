import logging

from szurubooru import db, rest
from szurubooru.rest import middleware

logger = logging.getLogger(__name__)


@middleware.pre_hook
def process_request(_ctx: rest.Context) -> None:
    db.reset_query_count()


@middleware.post_hook
def process_response(ctx: rest.Context) -> None:
    if ctx.accept == "application/json":
        logger.info(
            "%s %s (user=%s, queries=%d)",
            ctx.method,
            ctx.url,
            ctx.user.name,
            db.get_query_count(),
        )
    elif ctx.accept == "text/html":
        logger.info(
            "HTML %s (user-agent='%s' queries=%d)",
            ctx.url,
            ctx.get_header("User-Agent"),
            db.get_query_count(),
        )
