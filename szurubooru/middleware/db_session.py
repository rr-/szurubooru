''' Exports DbSession. '''

class DbSession(object):
    ''' Attaches database session to the context of every request. '''

    def __init__(self, session_factory):
        self._session_factory = session_factory

    def process_request(self, request, response):
        ''' Executed before passing the request to the API. '''
        request.context['session'] = self._session_factory()

    def process_response(self, request, response, resource):
        request.context['session'].close()
