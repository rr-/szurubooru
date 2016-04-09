from sqlalchemy.sql.expression import func
from szurubooru import db
from szurubooru.search.base_search_config import BaseSearchConfig

class UserSearchConfig(BaseSearchConfig):
    ''' Executes searches related to the users. '''

    def create_query(self, session):
        return session.query(db.User)

    @property
    def anonymous_filter(self):
        return self._create_basic_filter(db.User.name, allow_ranged=False)

    @property
    def special_filters(self):
        return {}

    @property
    def named_filters(self):
        return {
            'name': self._create_basic_filter(db.User.name, allow_ranged=False),
            'creation-date': self._create_date_filter(db.User.creation_time),
            'creation-time': self._create_date_filter(db.User.creation_time),
            'last-login-date': self._create_date_filter(db.User.last_login_time),
            'last-login-time': self._create_date_filter(db.User.last_login_time),
            'login-date': self._create_date_filter(db.User.last_login_time),
            'login-time': self._create_date_filter(db.User.last_login_time),
        }

    @property
    def order_columns(self):
        return {
            'random': func.random(),
            'name': db.User.name,
            'creation-date': db.User.creation_time,
            'creation-time': db.User.creation_time,
            'last-login-date': db.User.last_login_time,
            'last-login-time': db.User.last_login_time,
            'login-date': db.User.last_login_time,
            'login-time': db.User.last_login_time,
        }
