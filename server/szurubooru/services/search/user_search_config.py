''' Exports UserSearchConfig. '''

from sqlalchemy.sql.expression import func
from szurubooru.model import User
from szurubooru.services.search.base_search_config import BaseSearchConfig

class UserSearchConfig(BaseSearchConfig):
    ''' Executes searches related to the users. '''

    def create_query(self, session):
        return session.query(User)

    @property
    def anonymous_filter(self):
        return self._create_basic_filter(User.name, allow_ranged=False)

    @property
    def special_filters(self):
        return {}

    @property
    def named_filters(self):
        return {
            'name': self._create_basic_filter(User.name, allow_ranged=False),
            'creation_date': self._create_date_filter(User.creation_time),
            'creation_time': self._create_date_filter(User.creation_time),
            'last_login_date': self._create_date_filter(User.last_login_time),
            'last_login_time': self._create_date_filter(User.last_login_time),
            'login_date': self._create_date_filter(User.last_login_time),
            'login_time': self._create_date_filter(User.last_login_time),
        }

    @property
    def order_columns(self):
        return {
            'random': func.random(),
            'name': User.name,
            'creation_date': User.creation_time,
            'creation_time': User.creation_time,
            'last_login_date': User.last_login_time,
            'last_login_time': User.last_login_time,
            'login_date': User.last_login_time,
            'login_time': User.last_login_time,
        }
