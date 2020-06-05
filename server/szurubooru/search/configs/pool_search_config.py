from typing import Dict, Tuple

import sqlalchemy as sa

from szurubooru import db, model
from szurubooru.func import util
from szurubooru.search.configs import util as search_util
from szurubooru.search.configs.base_search_config import (
    BaseSearchConfig,
    Filter,
)
from szurubooru.search.typing import SaColumn, SaQuery


class PoolSearchConfig(BaseSearchConfig):
    def create_filter_query(self, _disable_eager_loads: bool) -> SaQuery:
        strategy = (
            sa.orm.lazyload if _disable_eager_loads else sa.orm.subqueryload
        )
        return (
            db.session.query(model.Pool)
            .join(model.PoolCategory)
            .options(strategy(model.Pool.names))
        )

    def create_count_query(self, _disable_eager_loads: bool) -> SaQuery:
        return db.session.query(model.Pool)

    def create_around_query(self) -> SaQuery:
        raise NotImplementedError()

    def finalize_query(self, query: SaQuery) -> SaQuery:
        return query.order_by(model.Pool.first_name.asc())

    @property
    def anonymous_filter(self) -> Filter:
        return search_util.create_subquery_filter(
            model.Pool.pool_id,
            model.PoolName.pool_id,
            model.PoolName.name,
            search_util.create_str_filter,
        )

    @property
    def named_filters(self) -> Dict[str, Filter]:
        return util.unalias_dict(
            [
                (
                    ["name"],
                    search_util.create_subquery_filter(
                        model.Pool.pool_id,
                        model.PoolName.pool_id,
                        model.PoolName.name,
                        search_util.create_str_filter,
                    ),
                ),
                (
                    ["category"],
                    search_util.create_subquery_filter(
                        model.Pool.category_id,
                        model.PoolCategory.pool_category_id,
                        model.PoolCategory.name,
                        search_util.create_str_filter,
                    ),
                ),
                (
                    ["creation-date", "creation-time"],
                    search_util.create_date_filter(model.Pool.creation_time),
                ),
                (
                    [
                        "last-edit-date",
                        "last-edit-time",
                        "edit-date",
                        "edit-time",
                    ],
                    search_util.create_date_filter(model.Pool.last_edit_time),
                ),
                (
                    ["post-count"],
                    search_util.create_num_filter(model.Pool.post_count),
                ),
            ]
        )

    @property
    def sort_columns(self) -> Dict[str, Tuple[SaColumn, str]]:
        return util.unalias_dict(
            [
                (
                    ["random"],
                    (sa.sql.expression.func.random(), self.SORT_NONE),
                ),
                (["name"], (model.Pool.first_name, self.SORT_ASC)),
                (["category"], (model.PoolCategory.name, self.SORT_ASC)),
                (
                    ["creation-date", "creation-time"],
                    (model.Pool.creation_time, self.SORT_DESC),
                ),
                (
                    [
                        "last-edit-date",
                        "last-edit-time",
                        "edit-date",
                        "edit-time",
                    ],
                    (model.Pool.last_edit_time, self.SORT_DESC),
                ),
                (["post-count"], (model.Pool.post_count, self.SORT_DESC)),
            ]
        )
