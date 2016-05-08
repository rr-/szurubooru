import logging
from szurubooru import config, db

logger = logging.getLogger(__name__)

class DbSession(object):
    ''' Attaches database session to the context of every request. '''

    def process_request(self, request, _response):
        request.context.session = db.session()
        db.reset_query_count()

    def process_response(self, _request, _response, _resource):
        db.session.remove()
        if config.config['debug']:
            logger.info('Executed %d queries', db.get_query_count())
