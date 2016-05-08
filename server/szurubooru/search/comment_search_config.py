from sqlalchemy.sql.expression import func
from szurubooru import db
from szurubooru.search.base_search_config import BaseSearchConfig

class CommentSearchConfig(BaseSearchConfig):
    def create_filter_query(self):
        return db.session.query(db.Comment).join(db.User)

    def finalize_query(self, query):
        return query.order_by(db.Comment.creation_time.desc())

    @property
    def anonymous_filter(self):
        return self._create_str_filter(db.Comment.text)

    @property
    def named_filters(self):
        return {
            'id': self._create_num_filter(db.Comment.comment_id),
            'post': self._create_num_filter(db.Comment.post_id),
            'user': self._create_str_filter(db.User.name),
            'author': self._create_str_filter(db.User.name),
            'text': self._create_str_filter(db.Comment.text),
            'creation-date': self._create_date_filter(db.Comment.creation_time),
            'creation-time': self._create_date_filter(db.Comment.creation_time),
            'last-edit-date': self._create_date_filter(db.Comment.last_edit_time),
            'last-edit-time': self._create_date_filter(db.Comment.last_edit_time),
            'edit-date': self._create_date_filter(db.Comment.last_edit_time),
            'edit-time': self._create_date_filter(db.Comment.last_edit_time),
        }

    @property
    def sort_columns(self):
        return {
            'random': (func.random(), None),
            'user': (db.User.name, self.SORT_ASC),
            'author': (db.User.name, self.SORT_ASC),
            'post': (db.Comment.post_id, self.SORT_DESC),
            'creation-date': (db.Comment.creation_time, self.SORT_DESC),
            'creation-time': (db.Comment.creation_time, self.SORT_DESC),
            'last-edit-date': (db.Comment.last_edit_time, self.SORT_DESC),
            'last-edit-time': (db.Comment.last_edit_time, self.SORT_DESC),
            'edit-date': (db.Comment.last_edit_time, self.SORT_DESC),
            'edit-time': (db.Comment.last_edit_time, self.SORT_DESC),
        }
