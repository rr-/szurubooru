''' Exports RequireJson. '''

import falcon

class RequireJson(object):
    ''' Sanitizes requests so that only JSON is accepted. '''

    def process_request(self, req, resp):
        ''' Executed before passing the request to the API. '''
        if not req.client_accepts_json:
            raise falcon.HTTPNotAcceptable(
                'This API only supports responses encoded as JSON.')
