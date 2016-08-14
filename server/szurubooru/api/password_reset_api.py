from szurubooru import config, errors
from szurubooru.func import auth, mailer, users, util
from szurubooru.rest import routes

MAIL_SUBJECT = 'Password reset for {name}'
MAIL_BODY = \
    'You (or someone else) requested to reset your password on {name}.\n' \
    'If you wish to proceed, click this link: {url}\n' \
    'Otherwise, please ignore this email.'

@routes.get('/password-reset/(?P<user_name>[^/]+)/?')
def start_password_reset(_ctx, params):
    ''' Send a mail with secure token to the correlated user. '''
    user_name = params['user_name']
    user = users.get_user_by_name_or_email(user_name)
    if not user.email:
        raise errors.ValidationError(
            'User %r hasn\'t supplied email. Cannot reset password.' % (
                user_name))
    token = auth.generate_authentication_token(user)
    url = '%s/password-reset/%s:%s' % (
        config.config['base_url'].rstrip('/'), user.name, token)
    mailer.send_mail(
        'noreply@%s' % config.config['name'],
        user.email,
        MAIL_SUBJECT.format(name=config.config['name']),
        MAIL_BODY.format(name=config.config['name'], url=url))
    return {}

@routes.post('/password-reset/(?P<user_name>[^/]+)/?')
def finish_password_reset(ctx, params):
    ''' Verify token from mail, generate a new password and return it. '''
    user_name = params['user_name']
    user = users.get_user_by_name_or_email(user_name)
    good_token = auth.generate_authentication_token(user)
    token = ctx.get_param_as_string('token', required=True)
    if token != good_token:
        raise errors.ValidationError('Invalid password reset token.')
    new_password = users.reset_user_password(user)
    util.bump_version(user)
    ctx.session.commit()
    return {'password': new_password}
