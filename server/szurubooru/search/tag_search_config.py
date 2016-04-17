from sqlalchemy.sql.expression import func
from szurubooru import db
from szurubooru.search.base_search_config import BaseSearchConfig

class TagSearchConfig(BaseSearchConfig):
    def create_query(self, session):
        return session.query(db.Tag)

    def finalize_query(self, query):
        return query.order_by(db.Tag.first_name.asc())

    @property
    def anonymous_filter(self):
        return self._create_str_filter(db.Tag.first_name)

    @property
    def special_filters(self):
        return {}

    @property
    def named_filters(self):
        return {
            'name': self._create_str_filter(db.Tag.first_name),
            'category': self._create_str_filter(db.Tag.category),
            'creation-date': self._create_date_filter(db.Tag.creation_time),
            'creation-time': self._create_date_filter(db.Tag.creation_time),
            'last-edit-date': self._create_date_filter(db.Tag.last_edit_time),
            'last-edit-time': self._create_date_filter(db.Tag.last_edit_time),
            'edit-date': self._create_date_filter(db.Tag.last_edit_time),
            'edit-time': self._create_date_filter(db.Tag.last_edit_time),
            'usages': self._create_num_filter(db.Tag.post_count),
            'usage-count': self._create_num_filter(db.Tag.post_count),
            'post-count': self._create_num_filter(db.Tag.post_count),
            'suggestion-count': self._create_num_filter(db.Tag.suggestion_count),
            'implication-count': self._create_num_filter(db.Tag.implication_count),
        }

    @property
    def order_columns(self):
        return {
            'random': (func.random(), None),
            'name': (db.Tag.first_name, self.ORDER_ASC),
            'category': (db.Tag.category, self.ORDER_ASC),
            'creation-date': (db.Tag.creation_time, self.ORDER_DESC),
            'creation-time': (db.Tag.creation_time, self.ORDER_DESC),
            'last-edit-date': (db.Tag.last_edit_time, self.ORDER_DESC),
            'last-edit-time': (db.Tag.last_edit_time, self.ORDER_DESC),
            'edit-date': (db.Tag.last_edit_time, self.ORDER_DESC),
            'edit-time': (db.Tag.last_edit_time, self.ORDER_DESC),
            'usages': (db.Tag.post_count, self.ORDER_DESC),
            'usage-count': (db.Tag.post_count, self.ORDER_DESC),
            'post-count': (db.Tag.post_count, self.ORDER_DESC),
            'suggestion-count': (db.Tag.suggestion_count, self.ORDER_DESC),
            'implication-count': (db.Tag.implication_count, self.ORDER_DESC),
        }
