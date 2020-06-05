from typing import Dict

from szurubooru import db, model
from szurubooru.search.configs import util as search_util
from szurubooru.search.configs.base_search_config import (
    BaseSearchConfig,
    Filter,
)
from szurubooru.search.typing import SaQuery


class SnapshotSearchConfig(BaseSearchConfig):
    def create_filter_query(self, _disable_eager_loads: bool) -> SaQuery:
        return db.session.query(model.Snapshot)

    def create_count_query(self, _disable_eager_loads: bool) -> SaQuery:
        return db.session.query(model.Snapshot)

    def create_around_query(self) -> SaQuery:
        raise NotImplementedError()

    def finalize_query(self, query: SaQuery) -> SaQuery:
        return query.order_by(model.Snapshot.creation_time.desc())

    @property
    def named_filters(self) -> Dict[str, Filter]:
        return {
            "type": search_util.create_str_filter(
                model.Snapshot.resource_type
            ),
            "id": search_util.create_str_filter(model.Snapshot.resource_name),
            "date": search_util.create_date_filter(
                model.Snapshot.creation_time
            ),
            "time": search_util.create_date_filter(
                model.Snapshot.creation_time
            ),
            "operation": search_util.create_str_filter(
                model.Snapshot.operation
            ),
            "user": search_util.create_str_filter(model.User.name),
        }
