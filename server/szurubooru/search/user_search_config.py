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
            'creation_date': self._create_date_filter(db.User.creation_time),
            'creation_time': self._create_date_filter(db.User.creation_time),
            'last_login_date': self._create_date_filter(db.User.last_login_time),
            'last_login_time': self._create_date_filter(db.User.last_login_time),
            'login_date': self._create_date_filter(db.User.last_login_time),
            'login_time': self._create_date_filter(db.User.last_login_time),
        }

    @property
    def order_columns(self):
        return {
            'random': func.random(),
            'name': db.User.name,
            'creation_date': db.User.creation_time,
            'creation_time': db.User.creation_time,
            'last_login_date': db.User.last_login_time,
            'last_login_time': db.User.last_login_time,
            'login_date': db.User.last_login_time,
            'login_time': db.User.last_login_time,
        }
