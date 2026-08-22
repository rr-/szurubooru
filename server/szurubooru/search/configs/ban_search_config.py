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


class BanSearchConfig(BaseSearchConfig):
    def create_filter_query(self, _disable_eager_loads: bool) -> SaQuery:
        return db.session.query(model.PostBan)

    def create_count_query(self, _disable_eager_loads: bool) -> SaQuery:
        return db.session.query(model.PostBan)

    def create_around_query(self) -> SaQuery:
        raise NotImplementedError()

    def finalize_query(self, query: SaQuery) -> SaQuery:
        return query.order_by(model.PostBan.time.asc())

    @property
    def anonymous_filter(self) -> Filter:
        return search_util.create_subquery_filter(
            model.PostBan.checksum,
            model.PostBan.time,
            search_util.create_str_filter,
        )

    @property
    def named_filters(self) -> Dict[str, Filter]:
        return util.unalias_dict(
            [
                (
                    ["time"],
                    search_util.create_date_filter(
                        model.PostBan.time,
                    ),
                ),
                (
                    ["checksum"],
                    search_util.create_subquery_filter(
                        model.PostBan.checksum,
                        search_util.create_str_filter,
                    ),
                )
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
                (["checksum"], (model.PostBan.checksum, self.SORT_ASC)),
                (["time"], (model.PostBan.time, self.SORT_ASC))
            ]
        )
