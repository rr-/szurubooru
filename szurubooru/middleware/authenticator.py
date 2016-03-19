import base64
import falcon

class Authenticator(object):
    def __init__(self, auth_service):
        self._auth_service = auth_service

    def process_request(self, request, response):
        request.context['user'] = self._get_user(request)

    def _get_user(self, request):
        if not request.auth:
            return self._auth_service.authenticate(None, None)

        try:
            auth_type, user_and_password = request.auth.split(' ', 1)

            if auth_type.lower() != 'basic':
                raise falcon.HTTPBadRequest(
                    'Invalid authentication type',
                    'Only basic authorization is supported.')

            username, password = base64.decodestring(
                user_and_password.encode('ascii')).decode('utf8').split(':')

            return self._auth_service.authenticate(username, password)
        except ValueError as err:
            msg = 'Basic authentication header value not properly formed. ' \
                + 'Supplied header {0}. Got error: {1}'
            raise falcon.HTTPBadRequest(
                'Malformed authentication request',
                msg.format(request.auth, str(err)))
