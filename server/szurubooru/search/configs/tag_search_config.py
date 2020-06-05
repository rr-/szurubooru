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


class TagSearchConfig(BaseSearchConfig):
    def create_filter_query(self, _disable_eager_loads: bool) -> SaQuery:
        strategy = (
            sa.orm.lazyload if _disable_eager_loads else sa.orm.subqueryload
        )
        return (
            db.session.query(model.Tag)
            .join(model.TagCategory)
            .options(
                sa.orm.defer(model.Tag.first_name),
                sa.orm.defer(model.Tag.suggestion_count),
                sa.orm.defer(model.Tag.implication_count),
                sa.orm.defer(model.Tag.post_count),
                strategy(model.Tag.names),
                strategy(model.Tag.suggestions).joinedload(model.Tag.names),
                strategy(model.Tag.implications).joinedload(model.Tag.names),
            )
        )

    def create_count_query(self, _disable_eager_loads: bool) -> SaQuery:
        return db.session.query(model.Tag)

    def create_around_query(self) -> SaQuery:
        raise NotImplementedError()

    def finalize_query(self, query: SaQuery) -> SaQuery:
        return query.order_by(model.Tag.first_name.asc())

    @property
    def anonymous_filter(self) -> Filter:
        return search_util.create_subquery_filter(
            model.Tag.tag_id,
            model.TagName.tag_id,
            model.TagName.name,
            search_util.create_str_filter,
        )

    @property
    def named_filters(self) -> Dict[str, Filter]:
        return util.unalias_dict(
            [
                (
                    ["name"],
                    search_util.create_subquery_filter(
                        model.Tag.tag_id,
                        model.TagName.tag_id,
                        model.TagName.name,
                        search_util.create_str_filter,
                    ),
                ),
                (
                    ["category"],
                    search_util.create_subquery_filter(
                        model.Tag.category_id,
                        model.TagCategory.tag_category_id,
                        model.TagCategory.name,
                        search_util.create_str_filter,
                    ),
                ),
                (
                    ["creation-date", "creation-time"],
                    search_util.create_date_filter(model.Tag.creation_time),
                ),
                (
                    [
                        "last-edit-date",
                        "last-edit-time",
                        "edit-date",
                        "edit-time",
                    ],
                    search_util.create_date_filter(model.Tag.last_edit_time),
                ),
                (
                    ["usage-count", "post-count", "usages"],
                    search_util.create_num_filter(model.Tag.post_count),
                ),
                (
                    ["suggestion-count"],
                    search_util.create_num_filter(model.Tag.suggestion_count),
                ),
                (
                    ["implication-count"],
                    search_util.create_num_filter(model.Tag.implication_count),
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
                (["name"], (model.Tag.first_name, self.SORT_ASC)),
                (["category"], (model.TagCategory.name, self.SORT_ASC)),
                (
                    ["creation-date", "creation-time"],
                    (model.Tag.creation_time, self.SORT_DESC),
                ),
                (
                    [
                        "last-edit-date",
                        "last-edit-time",
                        "edit-date",
                        "edit-time",
                    ],
                    (model.Tag.last_edit_time, self.SORT_DESC),
                ),
                (
                    ["usage-count", "post-count", "usages"],
                    (model.Tag.post_count, self.SORT_DESC),
                ),
                (
                    ["suggestion-count"],
                    (model.Tag.suggestion_count, self.SORT_DESC),
                ),
                (
                    ["implication-count"],
                    (model.Tag.implication_count, self.SORT_DESC),
                ),
            ]
        )
