from sqlalchemy.orm import subqueryload
from sqlalchemy.sql.expression import func
from szurubooru import db
from szurubooru.func import util
from szurubooru.search.configs import util as search_util
from szurubooru.search.configs.base_search_config import BaseSearchConfig

class TagSearchConfig(BaseSearchConfig):
    def create_filter_query(self):
        return self.create_count_query() \
            .join(db.TagCategory) \
            .options(
                subqueryload(db.Tag.names),
                subqueryload(db.Tag.category),
                subqueryload(db.Tag.suggestions).joinedload(db.Tag.names),
                subqueryload(db.Tag.implications).joinedload(db.Tag.names)
            )

    def create_count_query(self):
        return db.session.query(db.Tag)

    def finalize_query(self, query):
        return query.order_by(db.Tag.first_name.asc())

    @property
    def anonymous_filter(self):
        return search_util.create_subquery_filter(
            db.Tag.tag_id,
            db.TagName.tag_id,
            db.TagName.name,
            search_util.create_str_filter)

    @property
    def named_filters(self):
        return util.unalias_dict({
            'name': search_util.create_subquery_filter(
                db.Tag.tag_id,
                db.TagName.tag_id,
                db.TagName.name,
                search_util.create_str_filter),
            'category': search_util.create_subquery_filter(
                db.Tag.category_id,
                db.TagCategory.tag_category_id,
                db.TagCategory.name,
                search_util.create_str_filter),
            ('creation-date', 'creation-time'):
                search_util.create_date_filter(db.Tag.creation_time),
            ('last-edit-date', 'last-edit-time', 'edit-date', 'edit-time'):
                search_util.create_date_filter(db.Tag.last_edit_time),
            ('usage-count', 'post-count', 'usages'):
                search_util.create_num_filter(db.Tag.post_count),
            'suggestion-count':
                search_util.create_num_filter(db.Tag.suggestion_count),
            'implication-count':
                search_util.create_num_filter(db.Tag.implication_count),
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
