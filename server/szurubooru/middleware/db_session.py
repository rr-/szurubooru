from szurubooru import db

class DbSession(object):
    ''' Attaches database session to the context of every request. '''

    def process_request(self, request, _response):
        request.context.session = db.session()

    def process_response(self, _request, _response, _resource):
        db.session.remove()
