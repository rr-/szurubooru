''' Exports PasswordReminderApi. '''

import hashlib
from szurubooru.api.base_api import BaseApi
from szurubooru.errors import ValidationError, NotFoundError

class PasswordReminderApi(BaseApi):
    ''' API for password reminders. '''
    def __init__(self, config, mailer, user_service):
        super().__init__()
        self._config = config
        self._mailer = mailer
        self._user_service = user_service

    def get(self, request, context, user_name):
        user = self._user_service.get_by_name(context.session, user_name)
        if not user:
            raise NotFoundError('User %r not found.' % user_name)
        if not user.email:
            raise ValidationError(
                'User %r hasn\'t supplied email. Cannot reset password.' % user_name)
        token = self._generate_authentication_token(user)
        self._mailer.send(
            'noreply@%s' % self._config['basic']['name'],
            user.email,
            'Password reset for %s' % self._config['basic']['name'],
            'You (or someone else) requested to reset your password on %s.\n'
            'If you wish to proceed, click this link: %s/password-reset/%s\n'
            'Otherwise, please ignore this email.' %
                (self._config['basic']['name'],
                self._config['basic']['base_url'].rstrip('/'),
                token))
        return {}

    def post(self, request, context, user_name):
        user = self._user_service.get_by_name(context.session, user_name)
        if not user:
            raise NotFoundError('User %r not found.' % user_name)
        good_token = self._generate_authentication_token(user)
        if not 'token' in context.request:
            raise ValidationError('Missing password reset token.')
        token = context.request['token']
        if token != good_token:
            raise ValidationError('Invalid password reset token.')
        new_password = self._user_service.reset_password(user)
        context.session.commit()
        return {'password': new_password}

    def _generate_authentication_token(self, user):
        digest = hashlib.sha256()
        digest.update(self._config['basic']['secret'].encode('utf8'))
        digest.update(user.password_salt.encode('utf8'))
        return digest.hexdigest()
