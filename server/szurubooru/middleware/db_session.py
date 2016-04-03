class DbSession(object):
    ''' Attaches database session to the context of every request. '''

    def __init__(self, session_factory):
        self._session_factory = session_factory

    def process_request(self, request, _response):
        request.context.session = self._session_factory()

    def process_response(self, request, _response, _resource):
        # any commits need to happen explicitly in the API layer.
        request.context.session.close()
