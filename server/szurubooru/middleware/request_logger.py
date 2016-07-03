import logging
from szurubooru import db

logger = logging.getLogger(__name__)

class RequestLogger(object):
    def process_request(self, request, _response):
        pass

    def process_response(self, request, _response, _resource):
        logger.info(
            '%s (user=%s, queries=%d)',
            request.url,
            request.context.user.name,
            db.get_query_count())
