import hashlib
from szurubooru import errors
from szurubooru.api.base_api import BaseApi

MAIL_SUBJECT = 'Password reset for {name}'
MAIL_BODY = \
    'You (or someone else) requested to reset your password on {name}.\n' \
    'If you wish to proceed, click this link: {url}\n' \
    'Otherwise, please ignore this email.'

class PasswordReminderApi(BaseApi):
    def __init__(self, config, mailer, user_service):
        super().__init__()
        self._config = config
        self._mailer = mailer
        self._user_service = user_service

    def get(self, context, user_name):
        user = self._user_service.get_by_name(context.session, user_name)
        if not user:
            raise errors.NotFoundError('User %r not found.' % user_name)
        if not user.email:
            raise errors.ValidationError(
                'User %r hasn\'t supplied email. Cannot reset password.' % user_name)
        token = self._generate_authentication_token(user)
        url = '%s/password-reset/%s' % (
            self._config['basic']['base_url'].rstrip('/'), token)
        self._mailer.send(
            'noreply@%s' % self._config['basic']['name'],
            user.email,
            MAIL_SUBJECT.format(name=self._config['basic']['name']),
            MAIL_BODY.format(name=self._config['basic']['name'], url=url))
        return {}

    def post(self, context, user_name):
        user = self._user_service.get_by_name(context.session, user_name)
        if not user:
            raise errors.NotFoundError('User %r not found.' % user_name)
        good_token = self._generate_authentication_token(user)
        if not 'token' in context.request:
            raise errors.ValidationError('Missing password reset token.')
        token = context.request['token']
        if token != good_token:
            raise errors.ValidationError('Invalid password reset token.')
        new_password = self._user_service.reset_password(user)
        context.session.commit()
        return {'password': new_password}

    def _generate_authentication_token(self, user):
        digest = hashlib.sha256()
        digest.update(self._config['basic']['secret'].encode('utf8'))
        digest.update(user.password_salt.encode('utf8'))
        return digest.hexdigest()
