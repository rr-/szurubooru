class DbSession(object):
    ''' Attaches database session to the context of every request. '''

    def __init__(self, session_factory):
        self._session_factory = session_factory

    def process_request(self, request, _response):
        ''' Executed before passing the request to the API. '''
        request.context.session = self._session_factory()

    def process_response(self, request, _response, _resource):
        '''
        Executed before passing the response to falcon.
        Any commits to database need to happen explicitly in the API layer.
        '''
        request.context.session.close()
