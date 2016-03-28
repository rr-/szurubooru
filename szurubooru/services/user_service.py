''' Exports UserService. '''

from datetime import datetime
from szurubooru.model.user import User
from szurubooru.services.errors import IntegrityError

class UserService(object):
    ''' User management '''

    def __init__(self, config, transaction_manager, password_service):
        self._config = config
        self._transaction_manager = transaction_manager
        self._password_service = password_service

    def create_user(self, name, password, email):
        ''' Creates an user with given parameters and returns it. '''
        with self._transaction_manager.transaction() as session:
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

            try:
                session.add(user)
                session.commit()
            except:
                raise IntegrityError('User %r already exists.' % name)

            return user

    def get_by_name(self, name):
        ''' Retrieves an user by its name. '''
        with self._transaction_manager.read_only_transaction() as session:
            return session.query(User).filter_by(name=name).first()
