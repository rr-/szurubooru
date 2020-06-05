from typing import Dict, Tuple

import sqlalchemy as sa

from szurubooru import db, model
from szurubooru.search.configs import util as search_util
from szurubooru.search.configs.base_search_config import (
    BaseSearchConfig,
    Filter,
)
from szurubooru.search.typing import SaColumn, SaQuery


class UserSearchConfig(BaseSearchConfig):
    def create_filter_query(self, _disable_eager_loads: bool) -> SaQuery:
        return db.session.query(model.User)

    def create_count_query(self, _disable_eager_loads: bool) -> SaQuery:
        return db.session.query(model.User)

    def create_around_query(self) -> SaQuery:
        raise NotImplementedError()

    def finalize_query(self, query: SaQuery) -> SaQuery:
        return query.order_by(model.User.name.asc())

    @property
    def anonymous_filter(self) -> Filter:
        return search_util.create_str_filter(model.User.name)

    @property
    def named_filters(self) -> Dict[str, Filter]:
        return {
            "name": search_util.create_str_filter(model.User.name),
            "creation-date": search_util.create_date_filter(
                model.User.creation_time
            ),
            "creation-time": search_util.create_date_filter(
                model.User.creation_time
            ),
            "last-login-date": search_util.create_date_filter(
                model.User.last_login_time
            ),
            "last-login-time": search_util.create_date_filter(
                model.User.last_login_time
            ),
            "login-date": search_util.create_date_filter(
                model.User.last_login_time
            ),
            "login-time": search_util.create_date_filter(
                model.User.last_login_time
            ),
        }

    @property
    def sort_columns(self) -> Dict[str, Tuple[SaColumn, str]]:
        return {
            "random": (sa.sql.expression.func.random(), self.SORT_NONE),
            "name": (model.User.name, self.SORT_ASC),
            "creation-date": (model.User.creation_time, self.SORT_DESC),
            "creation-time": (model.User.creation_time, self.SORT_DESC),
            "last-login-date": (model.User.last_login_time, self.SORT_DESC),
            "last-login-time": (model.User.last_login_time, self.SORT_DESC),
            "login-date": (model.User.last_login_time, self.SORT_DESC),
            "login-time": (model.User.last_login_time, self.SORT_DESC),
        }
