import base64
import falcon
from szurubooru import errors
from szurubooru import model

class Authenticator(object):
    '''
    Authenticates every request and puts information on active user in the
    request context.
    '''

    def __init__(self, auth_service, user_service):
        self._auth_service = auth_service
        self._user_service = user_service

    def process_request(self, request, _response):
        ''' Executed before passing the request to the API. '''
        request.context.user = self._get_user(request)
        if request.get_param_as_bool('bump-login') \
                and request.context.user.user_id:
            self._user_service.bump_login_time(request.context.user)
            request.context.session.commit()

    def _get_user(self, request):
        if not request.auth:
            return self._create_anonymous_user()

        try:
            auth_type, user_and_password = request.auth.split(' ', 1)

            if auth_type.lower() != 'basic':
                raise falcon.HTTPBadRequest(
                    'Invalid authentication type',
                    'Only basic authorization is supported.')

            username, password = base64.decodebytes(
                user_and_password.encode('ascii')).decode('utf8').split(':')

            return self._authenticate(
                request.context.session, username, password)
        except ValueError as err:
            msg = 'Basic authentication header value not properly formed. ' \
                + 'Supplied header {0}. Got error: {1}'
            raise falcon.HTTPBadRequest(
                'Malformed authentication request',
                msg.format(request.auth, str(err)))

    def _authenticate(self, session, username, password):
        ''' Tries to authenticate user. Throws AuthError for invalid users. '''
        user = self._user_service.get_by_name(session, username)
        if not user:
            raise errors.AuthError('No such user.')
        if not self._auth_service.is_valid_password(user, password):
            raise errors.AuthError('Invalid password.')
        return user

    def _create_anonymous_user(self):
        user = model.User()
        user.name = None
        user.access_rank = 'anonymous'
        user.password = None
        return user
