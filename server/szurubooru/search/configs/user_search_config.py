from sqlalchemy.sql.expression import func
from szurubooru import db
from szurubooru.search.configs import util as search_util
from szurubooru.search.configs.base_search_config import BaseSearchConfig


class UserSearchConfig(BaseSearchConfig):
    def create_filter_query(self, _disable_eager_loads):
        return db.session.query(db.User)

    def create_count_query(self, _disable_eager_loads):
        return db.session.query(db.User)

    def create_around_query(self):
        raise NotImplementedError()

    def finalize_query(self, query):
        return query.order_by(db.User.name.asc())

    @property
    def anonymous_filter(self):
        return search_util.create_str_filter(db.User.name)

    @property
    def named_filters(self):
        return {
            'name': search_util.create_str_filter(db.User.name),
            'creation-date':
                search_util.create_date_filter(db.User.creation_time),
            'creation-time':
                search_util.create_date_filter(db.User.creation_time),
            'last-login-date':
                search_util.create_date_filter(db.User.last_login_time),
            'last-login-time':
                search_util.create_date_filter(db.User.last_login_time),
            'login-date':
                search_util.create_date_filter(db.User.last_login_time),
            'login-time':
                search_util.create_date_filter(db.User.last_login_time),
        }

    @property
    def sort_columns(self):
        return {
            'random': (func.random(), None),
            'name': (db.User.name, self.SORT_ASC),
            'creation-date': (db.User.creation_time, self.SORT_DESC),
            'creation-time': (db.User.creation_time, self.SORT_DESC),
            'last-login-date': (db.User.last_login_time, self.SORT_DESC),
            'last-login-time': (db.User.last_login_time, self.SORT_DESC),
            'login-date': (db.User.last_login_time, self.SORT_DESC),
            'login-time': (db.User.last_login_time, self.SORT_DESC),
        }
