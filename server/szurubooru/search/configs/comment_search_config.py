from typing import Dict, Tuple

import sqlalchemy as sa

from szurubooru import db, model
from szurubooru.search.configs import util as search_util
from szurubooru.search.configs.base_search_config import (
    BaseSearchConfig,
    Filter,
)
from szurubooru.search.typing import SaColumn, SaQuery


class CommentSearchConfig(BaseSearchConfig):
    def create_filter_query(self, _disable_eager_loads: bool) -> SaQuery:
        return db.session.query(model.Comment).join(model.User)

    def create_count_query(self, disable_eager_loads: bool) -> SaQuery:
        return self.create_filter_query(disable_eager_loads)

    def create_around_query(self) -> SaQuery:
        raise NotImplementedError()

    def finalize_query(self, query: SaQuery) -> SaQuery:
        return query.order_by(model.Comment.creation_time.desc())

    @property
    def anonymous_filter(self) -> SaQuery:
        return search_util.create_str_filter(model.Comment.text)

    @property
    def named_filters(self) -> Dict[str, Filter]:
        return {
            "id": search_util.create_num_filter(model.Comment.comment_id),
            "post": search_util.create_num_filter(model.Comment.post_id),
            "user": search_util.create_str_filter(model.User.name),
            "author": search_util.create_str_filter(model.User.name),
            "text": search_util.create_str_filter(model.Comment.text),
            "creation-date": search_util.create_date_filter(
                model.Comment.creation_time
            ),
            "creation-time": search_util.create_date_filter(
                model.Comment.creation_time
            ),
            "last-edit-date": search_util.create_date_filter(
                model.Comment.last_edit_time
            ),
            "last-edit-time": search_util.create_date_filter(
                model.Comment.last_edit_time
            ),
            "edit-date": search_util.create_date_filter(
                model.Comment.last_edit_time
            ),
            "edit-time": search_util.create_date_filter(
                model.Comment.last_edit_time
            ),
        }

    @property
    def sort_columns(self) -> Dict[str, Tuple[SaColumn, str]]:
        return {
            "random": (sa.sql.expression.func.random(), self.SORT_NONE),
            "user": (model.User.name, self.SORT_ASC),
            "author": (model.User.name, self.SORT_ASC),
            "post": (model.Comment.post_id, self.SORT_DESC),
            "creation-date": (model.Comment.creation_time, self.SORT_DESC),
            "creation-time": (model.Comment.creation_time, self.SORT_DESC),
            "last-edit-date": (model.Comment.last_edit_time, self.SORT_DESC),
            "last-edit-time": (model.Comment.last_edit_time, self.SORT_DESC),
            "edit-date": (model.Comment.last_edit_time, self.SORT_DESC),
            "edit-time": (model.Comment.last_edit_time, self.SORT_DESC),
        }
