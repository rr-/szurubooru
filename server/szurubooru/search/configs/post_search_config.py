from typing import Any, Dict, Optional, Tuple, Callable, Union

import sqlalchemy as sa

from szurubooru import db, errors, model
from szurubooru.func import util
from szurubooru.search import criteria, tokens
from szurubooru.search.configs import util as search_util
from szurubooru.search.configs.base_search_config import (
    BaseSearchConfig,
    Filter,
)
from szurubooru.search.query import SearchQuery
from szurubooru.search.typing import SaColumn, SaQuery


def _type_transformer(value: str) -> str:
    available_values = {
        "image": model.Post.TYPE_IMAGE,
        "animation": model.Post.TYPE_ANIMATION,
        "animated": model.Post.TYPE_ANIMATION,
        "anim": model.Post.TYPE_ANIMATION,
        "gif": model.Post.TYPE_ANIMATION,
        "video": model.Post.TYPE_VIDEO,
        "webm": model.Post.TYPE_VIDEO,
        "flash": model.Post.TYPE_FLASH,
        "swf": model.Post.TYPE_FLASH,
    }
    return search_util.enum_transformer(available_values, value)


def _safety_transformer(value: str) -> str:
    available_values = {
        "safe": model.Post.SAFETY_SAFE,
        "sketchy": model.Post.SAFETY_SKETCHY,
        "questionable": model.Post.SAFETY_SKETCHY,
        "unsafe": model.Post.SAFETY_UNSAFE,
    }
    return search_util.enum_transformer(available_values, value)


def _flag_transformer(value: str) -> str:
    available_values = {
        "loop": model.Post.FLAG_LOOP,
        "sound": model.Post.FLAG_SOUND,
    }
    return "%" + search_util.enum_transformer(available_values, value) + "%"


def _source_transformer(value: str) -> str:
    return search_util.wildcard_transformer("*" + value + "*")


def _create_score_filter(score: int) -> Filter:
    def wrapper(
        query: SaQuery,
        criterion: Optional[criteria.BaseCriterion],
        negated: bool,
    ) -> SaQuery:
        assert criterion
        if not getattr(criterion, "internal", False):
            raise errors.SearchError(
                "Votes cannot be seen publicly. Did you mean %r?"
                % "special:liked"
            )
        user_alias = sa.orm.aliased(model.User)
        score_alias = sa.orm.aliased(model.PostScore)
        expr = score_alias.score == score
        expr = expr & search_util.apply_str_criterion_to_column(
            user_alias.name, criterion
        )
        if negated:
            expr = ~expr
        ret = (
            query.join(score_alias, score_alias.post_id == model.Post.post_id)
            .join(user_alias, user_alias.user_id == score_alias.user_id)
            .filter(expr)
        )
        return ret

    return wrapper


def _user_filter(
    query: SaQuery, criterion: Optional[criteria.BaseCriterion], negated: bool
) -> SaQuery:
    assert criterion
    if isinstance(criterion, criteria.PlainCriterion) and not criterion.value:
        expr = model.Post.user_id == None  # noqa: E711
        if negated:
            expr = ~expr
        return query.filter(expr)
    return search_util.create_subquery_filter(
        model.Post.user_id,
        model.User.user_id,
        model.User.name,
        search_util.create_str_filter,
    )(query, criterion, negated)


def _note_filter(
    query: SaQuery, criterion: Optional[criteria.BaseCriterion], negated: bool
) -> SaQuery:
    assert criterion
    return search_util.create_subquery_filter(
        model.Post.post_id,
        model.PostNote.post_id,
        model.PostNote.text,
        search_util.create_str_filter,
    )(query, criterion, negated)


def _pool_filter(
    query: SaQuery, criterion: Optional[criteria.BaseCriterion], negated: bool
) -> SaQuery:
    assert criterion
    subquery = db.session.query(model.PoolPost.post_id.label("foreign_id"))
    subquery = subquery.options(sa.orm.lazyload("*"))
    subquery = search_util.create_num_filter(model.PoolPost.pool_id)(subquery, criterion, False)
    subquery = subquery.subquery("t")
    expression = model.Post.post_id.in_(subquery)
    if negated:
        expression = ~expression
    return query.filter(expression)


def _pool_sort(
    query: SaQuery, pool_id: Optional[int]
) -> SaQuery:
    if pool_id is None:
        return query
    return query.join(model.PoolPost, sa.and_(model.PoolPost.post_id == model.Post.post_id, model.PoolPost.pool_id == pool_id)) \
                .order_by(model.PoolPost.order.desc())


def _posts_around_pool(filter_query: SaQuery, post_id: int, pool_id: int) -> Tuple[SaQuery, SaQuery]:
    this_order = db.session.query(model.PoolPost) \
                 .filter(model.PoolPost.post_id == post_id) \
                 .filter(model.PoolPost.pool_id == pool_id) \
                 .one().order

    filter_query = db.session.query(model.Post) \
                             .join(model.PoolPost, model.PoolPost.pool_id == pool_id) \
                             .filter(model.PoolPost.post_id == model.Post.post_id)

    prev_filter_query = (
        filter_query.filter(model.PoolPost.order > this_order)
        .order_by(None)
        .order_by(sa.func.abs(model.PoolPost.order - this_order).asc())
        .limit(1)
    )
    next_filter_query = (
        filter_query.filter(model.PoolPost.order < this_order)
        .order_by(None)
        .order_by(sa.func.abs(model.PoolPost.order - this_order).asc())
        .limit(1)
    )
    return (prev_filter_query, next_filter_query)


def _category_filter(
    query: SaQuery, criterion: Optional[criteria.BaseCriterion], negated: bool
) -> SaQuery:
    assert criterion

    # Step 1. find the id for the category
    q1 = db.session.query(model.TagCategory.tag_category_id).filter(
        model.TagCategory.name == criterion.value
    )

    # Step 2. find the tags with that category
    q2 = db.session.query(model.Tag.tag_id).filter(
        model.Tag.category_id.in_(q1)
    )

    # Step 3. find all posts that have at least one of those tags
    q3 = db.session.query(model.PostTag.post_id).filter(
        model.PostTag.tag_id.in_(q2)
    )

    # Step 4. profit
    expr = model.Post.post_id.in_(q3)
    if negated:
        expr = ~expr

    return query.filter(expr)


class PostSearchConfig(BaseSearchConfig):
    def __init__(self) -> None:
        self.user = None  # type: Optional[model.User]
        self.pool_id = None  # type: Optional[int]

    def on_search_query_parsed(self, search_query: SearchQuery) -> SaQuery:
        new_special_tokens = []
        for token in search_query.special_tokens:
            if token.value in ("fav", "liked", "disliked"):
                assert self.user
                if self.user.rank == "anonymous":
                    raise errors.SearchError(
                        "Must be logged in to use this feature."
                    )
                criterion = criteria.PlainCriterion(
                    original_text=self.user.name, value=self.user.name
                )
                setattr(criterion, "internal", True)
                search_query.named_tokens.append(
                    tokens.NamedToken(
                        name=token.value,
                        criterion=criterion,
                        negated=token.negated,
                    )
                )
            else:
                new_special_tokens.append(token)
        search_query.special_tokens = new_special_tokens
        self.pool_id = None
        for token in search_query.named_tokens:
            if token.name == "pool" and isinstance(token.criterion, criteria.PlainCriterion):
                self.pool_id = token.criterion.value

    def create_around_query(self) -> SaQuery:
        return db.session.query(model.Post).options(sa.orm.lazyload("*"))

    def create_around_filter_queries(self, filter_query: SaQuery, entity_id: int) -> Tuple[SaQuery, SaQuery]:
        if self.pool_id is not None:
            return _posts_around_pool(filter_query, entity_id, self.pool_id)
        return super(PostSearchConfig, self).create_around_filter_queries(filter_query, entity_id)

    def create_filter_query(self, disable_eager_loads: bool) -> SaQuery:
        strategy = (
            sa.orm.lazyload if disable_eager_loads else sa.orm.subqueryload
        )
        return db.session.query(model.Post).options(
            sa.orm.lazyload("*"),
            # use config optimized for official client
            # sa.orm.defer(model.Post.score),
            # sa.orm.defer(model.Post.favorite_count),
            # sa.orm.defer(model.Post.comment_count),
            sa.orm.defer(model.Post.last_favorite_time),
            sa.orm.defer(model.Post.feature_count),
            sa.orm.defer(model.Post.last_feature_time),
            sa.orm.defer(model.Post.last_comment_creation_time),
            sa.orm.defer(model.Post.last_comment_edit_time),
            sa.orm.defer(model.Post.note_count),
            sa.orm.defer(model.Post.tag_count),
            strategy(model.Post.tags).subqueryload(model.Tag.names),
            strategy(model.Post.tags).defer(model.Tag.post_count),
            strategy(model.Post.tags).lazyload(model.Tag.implications),
            strategy(model.Post.tags).lazyload(model.Tag.suggestions),
        )

    def create_count_query(self, _disable_eager_loads: bool) -> SaQuery:
        return db.session.query(model.Post)

    def finalize_query(self, query: SaQuery) -> SaQuery:
        return query.order_by(model.Post.post_id.desc())

    @property
    def id_column(self) -> SaColumn:
        return model.Post.post_id

    @property
    def anonymous_filter(self) -> Optional[Filter]:
        return search_util.create_subquery_filter(
            model.Post.post_id,
            model.PostTag.post_id,
            model.TagName.name,
            search_util.create_str_filter,
            lambda subquery: subquery.join(model.Tag).join(model.TagName),
        )

    @property
    def named_filters(self) -> Dict[str, Filter]:
        return util.unalias_dict(
            [
                (["id"], search_util.create_num_filter(model.Post.post_id)),
                (
                    ["tag"],
                    search_util.create_subquery_filter(
                        model.Post.post_id,
                        model.PostTag.post_id,
                        model.TagName.name,
                        search_util.create_str_filter,
                        lambda subquery: subquery.join(model.Tag).join(
                            model.TagName
                        ),
                    ),
                ),
                (["score"], search_util.create_num_filter(model.Post.score)),
                (["uploader", "upload", "submit"], _user_filter),
                (
                    ["comment"],
                    search_util.create_subquery_filter(
                        model.Post.post_id,
                        model.Comment.post_id,
                        model.User.name,
                        search_util.create_str_filter,
                        lambda subquery: subquery.join(model.User),
                    ),
                ),
                (
                    ["fav"],
                    search_util.create_subquery_filter(
                        model.Post.post_id,
                        model.PostFavorite.post_id,
                        model.User.name,
                        search_util.create_str_filter,
                        lambda subquery: subquery.join(model.User),
                    ),
                ),
                (["liked"], _create_score_filter(1)),
                (["disliked"], _create_score_filter(-1)),
                (
                    ["source"],
                    search_util.create_str_filter(
                        model.Post.source, _source_transformer
                    ),
                ),
                (
                    ["tag-count"],
                    search_util.create_num_filter(model.Post.tag_count),
                ),
                (
                    ["comment-count"],
                    search_util.create_num_filter(model.Post.comment_count),
                ),
                (
                    ["fav-count"],
                    search_util.create_num_filter(model.Post.favorite_count),
                ),
                (
                    ["note-count"],
                    search_util.create_num_filter(model.Post.note_count),
                ),
                (
                    ["relation-count"],
                    search_util.create_num_filter(model.Post.relation_count),
                ),
                (
                    ["feature-count"],
                    search_util.create_num_filter(model.Post.feature_count),
                ),
                (
                    ["type"],
                    search_util.create_str_filter(
                        model.Post.type, _type_transformer
                    ),
                ),
                (
                    ["content-checksum", "sha1"],
                    search_util.create_str_filter(model.Post.checksum),
                ),
                (
                    ["md5"],
                    search_util.create_str_filter(model.Post.checksum_md5),
                ),
                (
                    ["file-size"],
                    search_util.create_num_filter(model.Post.file_size),
                ),
                (
                    ["image-width", "width"],
                    search_util.create_num_filter(model.Post.canvas_width),
                ),
                (
                    ["image-height", "height"],
                    search_util.create_num_filter(model.Post.canvas_height),
                ),
                (
                    ["image-area", "area"],
                    search_util.create_num_filter(model.Post.canvas_area),
                ),
                (
                    ["image-aspect-ratio", "image-ar", "aspect-ratio", "ar"],
                    search_util.create_num_filter(
                        model.Post.canvas_aspect_ratio,
                        transformer=search_util.float_transformer,
                    ),
                ),
                (
                    ["creation-date", "creation-time", "date", "time"],
                    search_util.create_date_filter(model.Post.creation_time),
                ),
                (
                    [
                        "last-edit-date",
                        "last-edit-time",
                        "edit-date",
                        "edit-time",
                    ],
                    search_util.create_date_filter(model.Post.last_edit_time),
                ),
                (
                    ["comment-date", "comment-time"],
                    search_util.create_date_filter(
                        model.Post.last_comment_creation_time
                    ),
                ),
                (
                    ["fav-date", "fav-time"],
                    search_util.create_date_filter(
                        model.Post.last_favorite_time
                    ),
                ),
                (
                    ["feature-date", "feature-time"],
                    search_util.create_date_filter(
                        model.Post.last_feature_time
                    ),
                ),
                (
                    ["safety", "rating"],
                    search_util.create_str_filter(
                        model.Post.safety, _safety_transformer
                    ),
                ),
                (["note-text"], _note_filter),
                (
                    ["flag"],
                    search_util.create_str_filter(
                        model.Post.flags_string, _flag_transformer
                    ),
                ),
                (["pool"], _pool_filter),
                (["category"], _category_filter),
            ]
        )

    @property
    def sort_columns(self) -> Dict[str, Union[Tuple[SaColumn, str], Callable[[SaQuery], None]]]:
        return util.unalias_dict(
            [
                (
                    ["random"],
                    (sa.sql.expression.func.random(), self.SORT_NONE),
                ),
                (["id"], (model.Post.post_id, self.SORT_DESC)),
                (["score"], (model.Post.score, self.SORT_DESC)),
                (["tag-count"], (model.Post.tag_count, self.SORT_DESC)),
                (
                    ["comment-count"],
                    (model.Post.comment_count, self.SORT_DESC),
                ),
                (["fav-count"], (model.Post.favorite_count, self.SORT_DESC)),
                (["note-count"], (model.Post.note_count, self.SORT_DESC)),
                (
                    ["relation-count"],
                    (model.Post.relation_count, self.SORT_DESC),
                ),
                (
                    ["feature-count"],
                    (model.Post.feature_count, self.SORT_DESC),
                ),
                (["file-size"], (model.Post.file_size, self.SORT_DESC)),
                (
                    ["image-width", "width"],
                    (model.Post.canvas_width, self.SORT_DESC),
                ),
                (
                    ["image-height", "height"],
                    (model.Post.canvas_height, self.SORT_DESC),
                ),
                (
                    ["image-area", "area"],
                    (model.Post.canvas_area, self.SORT_DESC),
                ),
                (
                    ["creation-date", "creation-time", "date", "time"],
                    (model.Post.creation_time, self.SORT_DESC),
                ),
                (
                    [
                        "last-edit-date",
                        "last-edit-time",
                        "edit-date",
                        "edit-time",
                    ],
                    (model.Post.last_edit_time, self.SORT_DESC),
                ),
                (
                    ["comment-date", "comment-time"],
                    (model.Post.last_comment_creation_time, self.SORT_DESC),
                ),
                (
                    ["fav-date", "fav-time"],
                    (model.Post.last_favorite_time, self.SORT_DESC),
                ),
                (
                    ["feature-date", "feature-time"],
                    (model.Post.last_feature_time, self.SORT_DESC),
                ),
                (
                    ["pool"],
                    lambda subquery: _pool_sort(subquery, self.pool_id)
                )
            ]
        )

    @property
    def special_filters(self) -> Dict[str, Filter]:
        return {
            # handled by parser
            "fav": self.noop_filter,
            "liked": self.noop_filter,
            "disliked": self.noop_filter,
            "tumbleweed": self.tumbleweed_filter,
        }

    def noop_filter(
        self,
        query: SaQuery,
        _criterion: Optional[criteria.BaseCriterion],
        _negated: bool,
    ) -> SaQuery:
        return query

    def tumbleweed_filter(
        self,
        query: SaQuery,
        _criterion: Optional[criteria.BaseCriterion],
        negated: bool,
    ) -> SaQuery:
        expr = (
            (model.Post.comment_count == 0)
            & (model.Post.favorite_count == 0)
            & (model.Post.score == 0)
        )
        if negated:
            expr = ~expr
        return query.filter(expr)
