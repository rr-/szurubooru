import sqlalchemy
from sqlalchemy.sql.expression import func
from szurubooru import db
from szurubooru.search.base_search_config import BaseSearchConfig

class TagSearchConfig(BaseSearchConfig):
    def __init__(self):
        self._session = None

    def create_query(self, session):
        self._session = session
        return session.query(db.Tag)

    def finalize_query(self, query):
        return query.order_by(self._first_name_subquery.asc())

    @property
    def anonymous_filter(self):
        return self._name_filter

    @property
    def special_filters(self):
        return {}

    @property
    def named_filters(self):
        return {
            'name': self._name_filter,
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
            'suggestion-count': self._suggestion_count_filter,
            'implication-count': self._implication_count_filter,
        }

    @property
    def order_columns(self):
        return {
            'random': (func.random(), None),
            'name': (self._first_name_subquery, self.ORDER_ASC),
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
            'suggestion-count':
                (self._suggestion_count_subquery, self.ORDER_DESC),
            'implication-count':
                (self._implication_count_subquery, self.ORDER_DESC),
        }

    def _name_filter(self, query, criterion):
        str_filter = self._create_str_filter(db.TagName.name)
        return query.filter(
            db.Tag.tag_id.in_(
                str_filter(self._session.query(db.TagName.tag_id), criterion)))

    def _suggestion_count_filter(self, query, criterion):
        return query.filter(
            self._apply_num_criterion_to_column(
                self._suggestion_count_subquery, criterion))

    def _implication_count_filter(self, query, criterion):
        return query.filter(
            self._apply_num_criterion_to_column(
                self._implication_count_subquery, criterion))

    @property
    def _first_name_subquery(self):
        return sqlalchemy.select([db.TagName.name]) \
            .limit(1) \
            .where(db.TagName.tag_id == db.Tag.tag_id) \
            .as_scalar()

    @property
    def _suggestion_count_subquery(self):
        return sqlalchemy.select([func.count(db.TagSuggestion.child_id)]) \
            .where(db.TagSuggestion.parent_id == db.Tag.tag_id) \
            .as_scalar()

    @property
    def _implication_count_subquery(self):
        return sqlalchemy.select([func.count(1)]) \
            .where(db.TagImplication.parent_id == db.Tag.tag_id) \
            .as_scalar()
