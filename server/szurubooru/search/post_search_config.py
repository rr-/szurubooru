from sqlalchemy.orm import subqueryload, lazyload, defer
from sqlalchemy.sql.expression import func
from szurubooru import db, errors
from szurubooru.func import util
from szurubooru.search.base_search_config import BaseSearchConfig

def _type_transformer(value):
    available_values = {
        'image': db.Post.TYPE_IMAGE,
        'animation': db.Post.TYPE_ANIMATION,
        'animated': db.Post.TYPE_ANIMATION,
        'anim': db.Post.TYPE_ANIMATION,
        'gif': db.Post.TYPE_ANIMATION,
        'video': db.Post.TYPE_VIDEO,
        'webm': db.Post.TYPE_VIDEO,
        'flash': db.Post.TYPE_FLASH,
        'swf': db.Post.TYPE_FLASH,
    }
    try:
        return available_values[value.lower()]
    except KeyError:
        raise errors.SearchError('Invalid value: %r. Available values: %r.' % (
            value, available_values))

def _safety_transformer(value):
    available_values = {
        'safe': db.Post.SAFETY_SAFE,
        'sketchy': db.Post.SAFETY_SKETCHY,
        'questionable': db.Post.SAFETY_SKETCHY,
        'unsafe': db.Post.SAFETY_UNSAFE,
    }
    try:
        return available_values[value.lower()]
    except KeyError:
        raise errors.SearchError('Invalid value: %r. Available values: %r.' % (
            value, available_values))

class PostSearchConfig(BaseSearchConfig):
    def create_filter_query(self):
        return self.create_count_query() \
            .options(
                # use config optimized for official client
                #defer(db.Post.score),
                #defer(db.Post.favorite_count),
                #defer(db.Post.comment_count),
                defer(db.Post.last_favorite_time),
                defer(db.Post.feature_count),
                defer(db.Post.last_feature_time),
                defer(db.Post.last_comment_creation_time),
                defer(db.Post.last_comment_edit_time),
                defer(db.Post.note_count),
                defer(db.Post.tag_count),
                subqueryload(db.Post.tags).subqueryload(db.Tag.names),
                lazyload(db.Post.user),
                lazyload(db.Post.relations),
                lazyload(db.Post.notes),
                lazyload(db.Post.favorited_by),
            )

    def create_count_query(self):
        return db.session.query(db.Post)

    def finalize_query(self, query):
        return query.order_by(db.Post.creation_time.desc())

    @property
    def anonymous_filter(self):
        return self._create_subquery_filter(
            db.Post.post_id,
            db.PostTag.post_id,
            db.TagName.name,
            self._create_str_filter,
            lambda subquery: subquery.join(db.Tag).join(db.TagName))

    @property
    def named_filters(self):
        return util.unalias_dict({
            'id': self._create_num_filter(db.Post.post_id),
            'tag': self._create_subquery_filter(
                db.Post.post_id,
                db.PostTag.post_id,
                db.TagName.name,
                self._create_str_filter,
                lambda subquery: subquery.join(db.Tag).join(db.TagName)),
            'score': self._create_num_filter(db.Post.score),
            ('uploader', 'upload', 'submit'):
                self._create_subquery_filter(
                    db.Post.user_id,
                    db.User.user_id,
                    db.User.name,
                    self._create_str_filter),
            'comment': self._create_subquery_filter(
                db.Post.post_id,
                db.Comment.post_id,
                db.User.name,
                self._create_str_filter,
                lambda subquery: subquery.join(db.User)),
            'fav': self._create_subquery_filter(
                db.Post.post_id,
                db.PostFavorite.post_id,
                db.User.name,
                self._create_str_filter,
                lambda subquery: subquery.join(db.User)),
            'tag-count': self._create_num_filter(db.Post.tag_count),
            'comment-count': self._create_num_filter(db.Post.comment_count),
            'fav-count': self._create_num_filter(db.Post.favorite_count),
            'note-count': self._create_num_filter(db.Post.note_count),
            'feature-count': self._create_num_filter(db.Post.feature_count),
            'type': self._create_str_filter(db.Post.type, _type_transformer),
            'file-size': self._create_num_filter(db.Post.file_size),
            ('image-width', 'width'):
                self._create_num_filter(db.Post.canvas_width),
            ('image-height', 'height'):
                self._create_num_filter(db.Post.canvas_height),
            ('image-area', 'area'):
                self._create_num_filter(db.Post.canvas_area),
            ('creation-date', 'creation-time', 'date', 'time'):
                self._create_date_filter(db.Post.creation_time),
            ('last-edit-date', 'last-edit-time', 'edit-date', 'edit-time'):
                self._create_date_filter(db.Post.last_edit_time),
            ('comment-date', 'comment-time'):
                self._create_date_filter(db.Post.last_comment_edit_time),
            ('fav-date', 'fav-time'):
                self._create_date_filter(db.Post.last_favorite_time),
            ('feature-date', 'feature-time'):
                self._create_date_filter(db.Post.last_feature_time),
            ('safety', 'rating'):
                self._create_str_filter(db.Post.safety, _safety_transformer),
        })

    @property
    def sort_columns(self):
        return util.unalias_dict({
            'random': (func.random(), None),
            'id': (db.Post.post_id, self.SORT_DESC),
            'score': (db.Post.score, self.SORT_DESC),
            'tag-count': (db.Post.tag_count, self.SORT_DESC),
            'comment-count': (db.Post.comment_count, self.SORT_DESC),
            'fav-count': (db.Post.favorite_count, self.SORT_DESC),
            'note-count': (db.Post.note_count, self.SORT_DESC),
            'feature-count': (db.Post.feature_count, self.SORT_DESC),
            'file-size': (db.Post.file_size, self.SORT_DESC),
            ('image-width', 'width'): (db.Post.canvas_width, self.SORT_DESC),
            ('image-height', 'height'): (db.Post.canvas_height, self.SORT_DESC),
            ('image-area', 'area'): (db.Post.canvas_area, self.SORT_DESC),
            ('creation-date', 'creation-time', 'date', 'time'):
                (db.Post.creation_time, self.SORT_DESC),
            ('last-edit-date', 'last-edit-time', 'edit-date', 'edit-time'):
                (db.Post.last_edit_time, self.SORT_DESC),
            ('comment-date', 'comment-time'):
                (db.Post.last_comment_edit_time, self.SORT_DESC),
            ('fav-date', 'fav-time'):
                (db.Post.last_favorite_time, self.SORT_DESC),
            ('feature-date', 'feature-time'):
                (db.Post.last_feature_time, self.SORT_DESC),
        })

    @property
    def special_filters(self):
        return {
            'liked': self.own_liked_filter,
            'disliked': self.own_disliked_filter,
            'fav': self.own_fav_filter,
            'tumbleweed': self.tumbleweed_filter,
        }

    def own_liked_filter(self, query, negated):
        assert self.user
        if self.user.rank == 'anonymous':
            raise errors.SearchError('Must be logged in to use this feature.')
        expr = db.Post.post_id.in_(
            db.session \
                .query(db.PostScore.post_id) \
                .filter(db.PostScore.user_id == self.user.user_id) \
                .filter(db.PostScore.score == 1))
        if negated:
            expr = ~expr
        return query.filter(expr)

    def own_disliked_filter(self, query, negated):
        assert self.user
        if self.user.rank == 'anonymous':
            raise errors.SearchError('Must be logged in to use this feature.')
        expr = db.Post.post_id.in_(
            db.session \
                .query(db.PostScore.post_id) \
                .filter(db.PostScore.user_id == self.user.user_id) \
                .filter(db.PostScore.score == -1))
        if negated:
            expr = ~expr
        return query.filter(expr)

    def own_fav_filter(self, query, negated):
        assert self.user
        if self.user.rank == 'anonymous':
            raise errors.SearchError('Must be logged in to use this feature.')
        expr = db.Post.post_id.in_(
            db.session \
                .query(db.PostFavorite.post_id) \
                .filter(db.PostFavorite.user_id == self.user.user_id))
        if negated:
            expr = ~expr
        return query.filter(expr)

    def tumbleweed_filter(self, query, negated):
        expr = \
            (db.Post.comment_count == 0) \
            & (db.Post.favorite_count == 0) \
            & (db.Post.score == 0)
        if negated:
            expr = ~expr
        return query.filter(expr)
