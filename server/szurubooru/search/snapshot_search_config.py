from szurubooru import db
from szurubooru.search.base_search_config import BaseSearchConfig

class SnapshotSearchConfig(BaseSearchConfig):
    def create_filter_query(self):
        return db.session.query(db.Snapshot)

    def finalize_query(self, query):
        return query.order_by(db.Snapshot.creation_time.desc())

    @property
    def named_filters(self):
        return {
            'type': self._create_str_filter(db.Snapshot.resource_type),
            'id': self._create_str_filter(db.Snapshot.resource_repr),
            'date': self._create_date_filter(db.Snapshot.creation_time),
            'time': self._create_date_filter(db.Snapshot.creation_time),
            'operation': self._create_str_filter(db.Snapshot.operation),
            'user': self._create_str_filter(db.User.name),
        }
