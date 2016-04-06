from szurubooru import config, errors
from szurubooru.util import auth, mailer, users
from szurubooru.api.base_api import BaseApi

MAIL_SUBJECT = 'Password reset for {name}'
MAIL_BODY = \
    'You (or someone else) requested to reset your password on {name}.\n' \
    'If you wish to proceed, click this link: {url}\n' \
    'Otherwise, please ignore this email.'

class PasswordResetApi(BaseApi):
    def get(self, context, user_name):
        ''' Send a mail with secure token to the correlated user. '''
        user = users.get_by_name_or_email(context.session, user_name)
        if not user:
            raise errors.NotFoundError('User %r not found.' % user_name)
        if not user.email:
            raise errors.ValidationError(
                'User %r hasn\'t supplied email. Cannot reset password.' % user_name)
        token = auth.generate_authentication_token(user)
        url = '%s/password-reset/%s:%s' % (
            config.config['basic']['base_url'].rstrip('/'), user.name, token)
        mailer.send_mail(
            'noreply@%s' % config.config['basic']['name'],
            user.email,
            MAIL_SUBJECT.format(name=config.config['basic']['name']),
            MAIL_BODY.format(name=config.config['basic']['name'], url=url))
        return {}

    def post(self, context, user_name):
        ''' Verify token from mail, generate a new password and return it. '''
        user = users.get_by_name_or_email(context.session, user_name)
        if not user:
            raise errors.NotFoundError('User %r not found.' % user_name)
        good_token = auth.generate_authentication_token(user)
        if not 'token' in context.request:
            raise errors.ValidationError('Missing password reset token.')
        token = context.request['token']
        if token != good_token:
            raise errors.ValidationError('Invalid password reset token.')
        new_password = users.reset_password(user)
        context.session.commit()
        return {'password': new_password}
