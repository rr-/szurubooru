from sqlalchemy.orm import joinedload
from sqlalchemy.sql.expression import func
from szurubooru import db
from szurubooru.func import util
from szurubooru.search.base_search_config import BaseSearchConfig

class TagSearchConfig(BaseSearchConfig):
    def create_filter_query(self):
        return self.create_count_query() \
            .join(db.TagCategory) \
            .options(
                joinedload(db.Tag.names),
                joinedload(db.Tag.category),
                joinedload(db.Tag.suggestions).joinedload(db.Tag.names),
                joinedload(db.Tag.implications).joinedload(db.Tag.names)
            )

    def create_count_query(self):
        return db.session.query(db.Tag)

    def finalize_query(self, query):
        return query.order_by(db.Tag.first_name.asc())

    @property
    def anonymous_filter(self):
        return self._create_str_filter(db.Tag.first_name)

    @property
    def named_filters(self):
        return util.unalias_dict({
            'name': self._create_str_filter(db.Tag.first_name),
            'category': self._create_subquery_filter(
                db.Tag.category_id,
                db.TagCategory.tag_category_id,
                db.TagCategory.name,
                self._create_str_filter),
            ('creation-date', 'creation-time'):
                self._create_date_filter(db.Tag.creation_time),
            ('last-edit-date', 'last-edit-time', 'edit-date', 'edit-time'):
                self._create_date_filter(db.Tag.last_edit_time),
            ('usage-count', 'post-count', 'usages'):
                self._create_num_filter(db.Tag.post_count),
            'suggestion-count': self._create_num_filter(db.Tag.suggestion_count),
            'implication-count': self._create_num_filter(db.Tag.implication_count),
        })

    @property
    def sort_columns(self):
        return util.unalias_dict({
            'random': (func.random(), None),
            'name': (db.Tag.first_name, self.SORT_ASC),
            'category': (db.TagCategory.name, self.SORT_ASC),
            ('creation-date', 'creation-time'):
                (db.Tag.creation_time, self.SORT_DESC),
            ('last-edit-date', 'last-edit-time', 'edit-date', 'edit-time'):
                (db.Tag.last_edit_time, self.SORT_DESC),
            ('usage-count', 'post-count', 'usages'):
                (db.Tag.post_count, self.SORT_DESC),
            'suggestion-count': (db.Tag.suggestion_count, self.SORT_DESC),
            'implication-count': (db.Tag.implication_count, self.SORT_DESC),
        })
