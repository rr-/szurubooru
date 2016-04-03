import re
from datetime import datetime
from szurubooru import errors
from szurubooru import model
from szurubooru import util

class UserService(object):
    ''' User management '''

    def __init__(self, config, password_service):
        self._config = config
        self._password_service = password_service
        self._name_regex = self._config['service']['user_name_regex']
        self._password_regex = self._config['service']['password_regex']

    def create_user(self, session, name, password, email):
        ''' Creates an user with given parameters and returns it. '''

        if not re.match(self._name_regex, name):
            raise errors.ValidationError(
                'Name must satisfy regex %r.' % self._name_regex)

        if not re.match(self._password_regex, password):
            raise errors.ValidationError(
                'Password must satisfy regex %r.' % self._password_regex)

        if not util.is_valid_email(email):
            raise errors.ValidationError(
                '%r is not a vaild email address.' % email)

        user = model.User()
        user.name = name
        user.password_salt = self._password_service.create_password()
        user.password_hash = self._password_service.get_password_hash(
            user.password_salt, password)
        user.email = email or None
        user.access_rank = self._config['service']['default_user_rank']
        user.creation_time = datetime.now()
        user.avatar_style = model.User.AVATAR_GRAVATAR

        session.add(user)
        return user

    def bump_login_time(self, user):
        user.last_login_time = datetime.now()

    def reset_password(self, user):
        password = self._password_service.create_password()
        user.password_salt = self._password_service.create_password()
        user.password_hash = self._password_service.get_password_hash(
            user.password_salt, password)
        return password

    def get_by_name(self, session, name):
        ''' Retrieves an user by its name. '''
        return session.query(model.User).filter_by(name=name).first()
