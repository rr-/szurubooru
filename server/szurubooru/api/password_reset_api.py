from szurubooru import config, errors
from szurubooru.api.base_api import BaseApi
from szurubooru.func import auth, mailer, users

MAIL_SUBJECT = 'Password reset for {name}'
MAIL_BODY = \
    'You (or someone else) requested to reset your password on {name}.\n' \
    'If you wish to proceed, click this link: {url}\n' \
    'Otherwise, please ignore this email.'

class PasswordResetApi(BaseApi):
    def get(self, _ctx, user_name):
        ''' Send a mail with secure token to the correlated user. '''
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

    def post(self, ctx, user_name):
        ''' Verify token from mail, generate a new password and return it. '''
        user = users.get_user_by_name_or_email(user_name)
        good_token = auth.generate_authentication_token(user)
        token = ctx.get_param_as_string('token', required=True)
        if token != good_token:
            raise errors.ValidationError('Invalid password reset token.')
        new_password = users.reset_user_password(user)
        ctx.session.commit()
        return {'password': new_password}
