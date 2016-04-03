''' Exports UserService. '''

import re
from datetime import datetime
from szurubooru.errors import ValidationError
from szurubooru.model.user import User

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
            raise ValidationError(
                'Name must satisfy regex %r.' % self._name_regex)

        if not re.match(self._password_regex, password):
            raise ValidationError(
                'Password must satisfy regex %r.' % self._password_regex)

        # prefer nulls to empty strings in the DB
        if not email:
            email = None

        user = User()
        user.name = name
        user.password = password
        user.password_salt = self._password_service.create_password()
        user.password_hash = self._password_service.get_password_hash(
            user.password_salt, user.password)
        user.email = email
        user.access_rank = self._config['service']['default_user_rank']
        user.creation_time = datetime.now()
        user.avatar_style = User.AVATAR_GRAVATAR

        session.add(user)
        return user

    def bump_login_time(self, user):
        user.last_login_time = datetime.now()

    def get_by_name(self, session, name):
        ''' Retrieves an user by its name. '''
        return session.query(User).filter_by(name=name).first()
