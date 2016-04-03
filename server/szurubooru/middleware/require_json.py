import falcon

class RequireJson(object):
    ''' Sanitizes requests so that only JSON is accepted. '''

    def process_request(self, request, _response):
        if not request.client_accepts_json:
            raise falcon.HTTPNotAcceptable(
                'This API only supports responses encoded as JSON.')
