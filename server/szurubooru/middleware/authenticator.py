import base64
from szurubooru import db, errors
from szurubooru.func import auth, users
from szurubooru.rest import middleware
from szurubooru.rest.errors import HttpBadRequest


def _authenticate(username, password):
    ''' Try to authenticate user. Throw AuthError for invalid users. '''
    user = users.get_user_by_name(username)
    if not auth.is_valid_password(user, password):
        raise errors.AuthError('Invalid password.')
    return user


def _create_anonymous_user():
    user = db.User()
    user.name = None
    user.rank = 'anonymous'
    return user


def _get_user(ctx):
    if not ctx.has_header('Authorization'):
        return _create_anonymous_user()

    try:
        auth_type, credentials = ctx.get_header('Authorization').split(' ', 1)
        if auth_type.lower() != 'basic':
            raise HttpBadRequest(
                'ValidationError',
                'Only basic HTTP authentication is supported.')
        username, password = base64.decodebytes(
            credentials.encode('ascii')).decode('utf8').split(':')
        return _authenticate(username, password)
    except ValueError as err:
        msg = 'Basic authentication header value are not properly formed. ' \
            + 'Supplied header {0}. Got error: {1}'
        raise HttpBadRequest(
            'ValidationError',
            msg.format(ctx.get_header('Authorization'), str(err)))


@middleware.pre_hook
def process_request(ctx):
    ''' Bind the user to request. Update last login time if needed. '''
    ctx.user = _get_user(ctx)
    if ctx.get_param_as_bool('bump-login') and ctx.user.user_id:
        users.bump_user_login_time(ctx.user)
        ctx.session.commit()
