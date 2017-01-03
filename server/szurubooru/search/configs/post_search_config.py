from sqlalchemy.orm import subqueryload, lazyload, defer, aliased
from sqlalchemy.sql.expression import func
from szurubooru import db, errors
from szurubooru.func import util
from szurubooru.search import criteria, tokens
from szurubooru.search.configs import util as search_util
from szurubooru.search.configs.base_search_config import BaseSearchConfig


def _enum_transformer(available_values, value):
    try:
        return available_values[value.lower()]
    except KeyError:
        raise errors.SearchError(
            'Invalid value: %r. Possible values: %r.' % (
                value, list(sorted(available_values.keys()))))


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
    return _enum_transformer(available_values, value)


def _safety_transformer(value):
    available_values = {
        'safe': db.Post.SAFETY_SAFE,
        'sketchy': db.Post.SAFETY_SKETCHY,
        'questionable': db.Post.SAFETY_SKETCHY,
        'unsafe': db.Post.SAFETY_UNSAFE,
    }
    return _enum_transformer(available_values, value)


def _create_score_filter(score):
    def wrapper(query, criterion, negated):
        if not getattr(criterion, 'internal', False):
            raise errors.SearchError(
                'Votes cannot be seen publicly. Did you mean %r?'
                    % 'special:liked')
        user_alias = aliased(db.User)
        score_alias = aliased(db.PostScore)
        expr = score_alias.score == score
        expr = expr & search_util.apply_str_criterion_to_column(
            user_alias.name, criterion)
        if negated:
            expr = ~expr
        ret = query \
            .join(score_alias, score_alias.post_id == db.Post.post_id) \
            .join(user_alias, user_alias.user_id == score_alias.user_id) \
            .filter(expr)
        return ret
    return wrapper


def _create_user_filter():
    def wrapper(query, criterion, negated):
        if isinstance(criterion, criteria.PlainCriterion) \
                and not criterion.value:
            # pylint: disable=singleton-comparison
            expr = db.Post.user_id == None
            if negated:
                expr = ~expr
            return query.filter(expr)
        return search_util.create_subquery_filter(
            db.Post.user_id,
            db.User.user_id,
            db.User.name,
            search_util.create_str_filter)(query, criterion, negated)
    return wrapper


class PostSearchConfig(BaseSearchConfig):
    def on_search_query_parsed(self, search_query):
        new_special_tokens = []
        for token in search_query.special_tokens:
            if token.value in ('fav', 'liked', 'disliked'):
                assert self.user
                if self.user.rank == 'anonymous':
                    raise errors.SearchError(
                        'Must be logged in to use this feature.')
                criterion = criteria.PlainCriterion(
                    original_text=self.user.name,
                    value=self.user.name)
                criterion.internal = True
                search_query.named_tokens.append(
                    tokens.NamedToken(
                        name=token.value,
                        criterion=criterion,
                        negated=token.negated))
            else:
                new_special_tokens.append(token)
        search_query.special_tokens = new_special_tokens

    def create_around_query(self):
        return db.session.query(db.Post.post_id)

    def create_filter_query(self, disable_eager_loads):
        strategy = lazyload if disable_eager_loads else subqueryload
        return db.session.query(db.Post) \
            .options(
                lazyload('*'),
                # use config optimized for official client
                # defer(db.Post.score),
                # defer(db.Post.favorite_count),
                # defer(db.Post.comment_count),
                defer(db.Post.last_favorite_time),
                defer(db.Post.feature_count),
                defer(db.Post.last_feature_time),
                defer(db.Post.last_comment_creation_time),
                defer(db.Post.last_comment_edit_time),
                defer(db.Post.note_count),
                defer(db.Post.tag_count),
                strategy(db.Post.tags).subqueryload(db.Tag.names),
                strategy(db.Post.tags).defer(db.Tag.post_count),
                strategy(db.Post.tags).lazyload(db.Tag.implications),
                strategy(db.Post.tags).lazyload(db.Tag.suggestions))

    def create_count_query(self, _disable_eager_loads):
        return db.session.query(db.Post)

    def finalize_query(self, query):
        return query.order_by(db.Post.post_id.desc())

    @property
    def id_column(self):
        return db.Post.post_id

    @property
    def anonymous_filter(self):
        return search_util.create_subquery_filter(
            db.Post.post_id,
            db.PostTag.post_id,
            db.TagName.name,
            search_util.create_str_filter,
            lambda subquery: subquery.join(db.Tag).join(db.TagName))

    @property
    def named_filters(self):
        return util.unalias_dict({
            'id': search_util.create_num_filter(db.Post.post_id),
            'tag': search_util.create_subquery_filter(
                db.Post.post_id,
                db.PostTag.post_id,
                db.TagName.name,
                search_util.create_str_filter,
                lambda subquery: subquery.join(db.Tag).join(db.TagName)),
            'score': search_util.create_num_filter(db.Post.score),
            ('uploader', 'upload', 'submit'):
                _create_user_filter(),
            'comment': search_util.create_subquery_filter(
                db.Post.post_id,
                db.Comment.post_id,
                db.User.name,
                search_util.create_str_filter,
                lambda subquery: subquery.join(db.User)),
            'fav': search_util.create_subquery_filter(
                db.Post.post_id,
                db.PostFavorite.post_id,
                db.User.name,
                search_util.create_str_filter,
                lambda subquery: subquery.join(db.User)),
            'liked': _create_score_filter(1),
            'disliked': _create_score_filter(-1),
            'tag-count': search_util.create_num_filter(db.Post.tag_count),
            'comment-count':
                search_util.create_num_filter(db.Post.comment_count),
            'fav-count':
                search_util.create_num_filter(db.Post.favorite_count),
            'note-count': search_util.create_num_filter(db.Post.note_count),
            'relation-count':
                search_util.create_num_filter(db.Post.relation_count),
            'feature-count':
                search_util.create_num_filter(db.Post.feature_count),
            'type':
                search_util.create_str_filter(
                    db.Post.type, _type_transformer),
            'content-checksum': search_util.create_str_filter(
                db.Post.checksum),
            'file-size': search_util.create_num_filter(db.Post.file_size),
            ('image-width', 'width'):
                search_util.create_num_filter(db.Post.canvas_width),
            ('image-height', 'height'):
                search_util.create_num_filter(db.Post.canvas_height),
            ('image-area', 'area'):
                search_util.create_num_filter(db.Post.canvas_area),
            ('creation-date', 'creation-time', 'date', 'time'):
                search_util.create_date_filter(db.Post.creation_time),
            ('last-edit-date', 'last-edit-time', 'edit-date', 'edit-time'):
                search_util.create_date_filter(db.Post.last_edit_time),
            ('comment-date', 'comment-time'):
                search_util.create_date_filter(
                    db.Post.last_comment_creation_time),
            ('fav-date', 'fav-time'):
                search_util.create_date_filter(db.Post.last_favorite_time),
            ('feature-date', 'feature-time'):
                search_util.create_date_filter(db.Post.last_feature_time),
            ('safety', 'rating'):
                search_util.create_str_filter(
                    db.Post.safety, _safety_transformer),
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
            'relation-count': (db.Post.relation_count, self.SORT_DESC),
            'feature-count': (db.Post.feature_count, self.SORT_DESC),
            'file-size': (db.Post.file_size, self.SORT_DESC),
            ('image-width', 'width'):
                (db.Post.canvas_width, self.SORT_DESC),
            ('image-height', 'height'):
                (db.Post.canvas_height, self.SORT_DESC),
            ('image-area', 'area'):
                (db.Post.canvas_area, self.SORT_DESC),
            ('creation-date', 'creation-time', 'date', 'time'):
                (db.Post.creation_time, self.SORT_DESC),
            ('last-edit-date', 'last-edit-time', 'edit-date', 'edit-time'):
                (db.Post.last_edit_time, self.SORT_DESC),
            ('comment-date', 'comment-time'):
                (db.Post.last_comment_creation_time, self.SORT_DESC),
            ('fav-date', 'fav-time'):
                (db.Post.last_favorite_time, self.SORT_DESC),
            ('feature-date', 'feature-time'):
                (db.Post.last_feature_time, self.SORT_DESC),
        })

    @property
    def special_filters(self):
        return {
            # handled by parsed
            'fav': None,
            'liked': None,
            'disliked': None,
            'tumbleweed': self.tumbleweed_filter,
        }

    def tumbleweed_filter(self, query, negated):
        expr = \
            (db.Post.comment_count == 0) \
            & (db.Post.favorite_count == 0) \
            & (db.Post.score == 0)
        if negated:
            expr = ~expr
        return query.filter(expr)
