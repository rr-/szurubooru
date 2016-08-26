from szurubooru import db
from szurubooru.search.configs import util as search_util
from szurubooru.search.configs.base_search_config import BaseSearchConfig


class SnapshotSearchConfig(BaseSearchConfig):
    def create_filter_query(self, _disable_eager_loads):
        return db.session.query(db.Snapshot)

    def create_count_query(self, _disable_eager_loads):
        return db.session.query(db.Snapshot)

    def create_around_query(self):
        raise NotImplementedError()

    def finalize_query(self, query):
        return query.order_by(db.Snapshot.creation_time.desc())

    @property
    def named_filters(self):
        return {
            'type': search_util.create_str_filter(db.Snapshot.resource_type),
            'id': search_util.create_str_filter(db.Snapshot.resource_name),
            'date': search_util.create_date_filter(db.Snapshot.creation_time),
            'time': search_util.create_date_filter(db.Snapshot.creation_time),
            'operation': search_util.create_str_filter(db.Snapshot.operation),
            'user': search_util.create_str_filter(db.User.name),
        }
