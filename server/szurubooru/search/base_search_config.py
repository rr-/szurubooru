import sqlalchemy
import szurubooru.errors
from szurubooru import db, errors
from szurubooru.func import util
from szurubooru.search import criteria

def wildcard_transformer(value):
    return value.replace('*', '%')

class BaseSearchConfig(object):
    SORT_DESC = -1
    SORT_ASC = 1

    def create_filter_query(self):
        raise NotImplementedError()

    def create_count_query(self):
        return self.create_filter_query()

    @property
    def anonymous_filter(self):
        return None

    @property
    def special_filters(self):
        return {}

    @property
    def named_filters(self):
        return {}

    @property
    def sort_columns(self):
        return {}

    @staticmethod
    def _apply_num_criterion_to_column(column, criterion):
        '''
        Decorate SQLAlchemy filter on given column using supplied criterion.
        '''
        if isinstance(criterion, criteria.PlainSearchCriterion):
            expr = column == int(criterion.value)
        elif isinstance(criterion, criteria.ArraySearchCriterion):
            expr = column.in_(int(value) for value in criterion.values)
        elif isinstance(criterion, criteria.RangedSearchCriterion):
            assert criterion.min_value != '' \
                or criterion.max_value != ''
            if criterion.min_value != '' and criterion.max_value != '':
                expr = column.between(
                    int(criterion.min_value), int(criterion.max_value))
            elif criterion.min_value != '':
                expr = column >= int(criterion.min_value)
            elif criterion.max_value != '':
                expr = column <= int(criterion.max_value)
        else:
            assert False
        if criterion.negated:
            expr = ~expr
        return expr

    @staticmethod
    def _create_num_filter(column):
        return lambda query, criterion: query.filter(
            BaseSearchConfig._apply_num_criterion_to_column(column, criterion))

    @staticmethod
    def _apply_str_criterion_to_column(column, criterion, transformer):
        '''
        Decorate SQLAlchemy filter on given column using supplied criterion.
        '''
        if isinstance(criterion, criteria.PlainSearchCriterion):
            expr = column.ilike(transformer(criterion.value))
        elif isinstance(criterion, criteria.ArraySearchCriterion):
            expr = sqlalchemy.sql.false()
            for value in criterion.values:
                expr = expr | column.ilike(transformer(value))
        elif isinstance(criterion, criteria.RangedSearchCriterion):
            raise errors.SearchError(
                'Composite token %r is invalid in this context.' % (criterion,))
        else:
            assert False
        if criterion.negated:
            expr = ~expr
        return expr

    @staticmethod
    def _create_str_filter(column, transformer=wildcard_transformer):
        return lambda query, criterion: query.filter(
            BaseSearchConfig._apply_str_criterion_to_column(
                column, criterion, transformer))

    @staticmethod
    def _apply_date_criterion_to_column(column, criterion):
        '''
        Decorate SQLAlchemy filter on given column using supplied criterion.
        Parse the datetime inside the criterion.
        '''
        if isinstance(criterion, criteria.PlainSearchCriterion):
            min_date, max_date = util.parse_time_range(criterion.value)
            expr = column.between(min_date, max_date)
        elif isinstance(criterion, criteria.ArraySearchCriterion):
            expr = sqlalchemy.sql.false()
            for value in criterion.values:
                min_date, max_date = util.parse_time_range(value)
                expr = expr | column.between(min_date, max_date)
        elif isinstance(criterion, criteria.RangedSearchCriterion):
            assert criterion.min_value or criterion.max_value
            if criterion.min_value and criterion.max_value:
                min_date = util.parse_time_range(criterion.min_value)[0]
                max_date = util.parse_time_range(criterion.max_value)[1]
                expr = column.between(min_date, max_date)
            elif criterion.min_value:
                min_date = util.parse_time_range(criterion.min_value)[0]
                expr = column >= min_date
            elif criterion.max_value:
                max_date = util.parse_time_range(criterion.max_value)[1]
                expr = column <= max_date
        else:
            assert False
        if criterion.negated:
            expr = ~expr
        return expr

    @staticmethod
    def _create_date_filter(column):
        return lambda query, criterion: query.filter(
            BaseSearchConfig._apply_date_criterion_to_column(column, criterion))

    @staticmethod
    def _create_subquery_filter(
            left_id_column,
            right_id_column,
            filter_column,
            filter_factory,
            subquery_decorator=None):
        filter_func = filter_factory(filter_column)
        def func(query, criterion):
            subquery = db.session.query(right_id_column.label('foreign_id'))
            if subquery_decorator:
                subquery = subquery_decorator(subquery)
            subquery = subquery.options(sqlalchemy.orm.lazyload('*'))
            subquery = filter_func(subquery, criterion)
            subquery = subquery.subquery('t')
            return query.filter(left_id_column.in_(subquery))
        return func
