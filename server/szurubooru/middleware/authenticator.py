import base64
import falcon
from szurubooru import db, errors
from szurubooru.util import auth, users

class Authenticator(object):
    '''
    Authenticates every request and put information on active user in the
    request context.
    '''

    def process_request(self, request, _response):
        ''' Bind the user to request. Update last login time if needed. '''
        request.context.user = self._get_user(request)
        if request.get_param_as_bool('bump-login') \
                and request.context.user.user_id:
            users.bump_login_time(request.context.user)
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
        ''' Try to authenticate user. Throw AuthError for invalid users. '''
        user = users.get_by_name(session, username)
        if not user:
            raise errors.AuthError('No such user.')
        if not auth.is_valid_password(user, password):
            raise errors.AuthError('Invalid password.')
        return user

    def _create_anonymous_user(self):
        user = db.User()
        user.name = None
        user.rank = 'anonymous'
        user.password = None
        return user
