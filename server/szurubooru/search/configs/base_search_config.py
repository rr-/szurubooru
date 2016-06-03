import sqlalchemy
from szurubooru import db, errors
from szurubooru.func import util
from szurubooru.search import criteria, tokens

def wildcard_transformer(value):
    return value.replace('*', '%')

class BaseSearchConfig(object):
    SORT_ASC = tokens.SortToken.SORT_ASC
    SORT_DESC = tokens.SortToken.SORT_DESC

    def on_search_query_parsed(self, search_query):
        pass

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
        try:
            if isinstance(criterion, criteria.PlainCriterion):
                expr = column == int(criterion.value)
            elif isinstance(criterion, criteria.ArrayCriterion):
                expr = column.in_(int(value) for value in criterion.values)
            elif isinstance(criterion, criteria.RangedCriterion):
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
        except ValueError:
            raise errors.SearchError(
                'Criterion value %r must be a number.' % (criterion,))
        return expr

    @staticmethod
    def _create_num_filter(column):
        def wrapper(query, criterion, negated):
            expr = BaseSearchConfig._apply_num_criterion_to_column(
                column, criterion)
            if negated:
                expr = ~expr
            return query.filter(expr)
        return wrapper

    @staticmethod
    def _apply_str_criterion_to_column(
            column, criterion, transformer=wildcard_transformer):
        '''
        Decorate SQLAlchemy filter on given column using supplied criterion.
        '''
        if isinstance(criterion, criteria.PlainCriterion):
            expr = column.ilike(transformer(criterion.value))
        elif isinstance(criterion, criteria.ArrayCriterion):
            expr = sqlalchemy.sql.false()
            for value in criterion.values:
                expr = expr | column.ilike(transformer(value))
        elif isinstance(criterion, criteria.RangedCriterion):
            raise errors.SearchError(
                'Composite token %r is invalid in this context.' % (criterion,))
        else:
            assert False
        return expr

    @staticmethod
    def _create_str_filter(column, transformer=wildcard_transformer):
        def wrapper(query, criterion, negated):
            expr = BaseSearchConfig._apply_str_criterion_to_column(
                column, criterion, transformer)
            if negated:
                expr = ~expr
            return query.filter(expr)
        return wrapper

    @staticmethod
    def _apply_date_criterion_to_column(column, criterion):
        '''
        Decorate SQLAlchemy filter on given column using supplied criterion.
        Parse the datetime inside the criterion.
        '''
        if isinstance(criterion, criteria.PlainCriterion):
            min_date, max_date = util.parse_time_range(criterion.value)
            expr = column.between(min_date, max_date)
        elif isinstance(criterion, criteria.ArrayCriterion):
            expr = sqlalchemy.sql.false()
            for value in criterion.values:
                min_date, max_date = util.parse_time_range(value)
                expr = expr | column.between(min_date, max_date)
        elif isinstance(criterion, criteria.RangedCriterion):
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
        return expr

    @staticmethod
    def _create_date_filter(column):
        def wrapper(query, criterion, negated):
            expr = BaseSearchConfig._apply_date_criterion_to_column(
                column, criterion)
            if negated:
                expr = ~expr
            return query.filter(expr)
        return wrapper

    @staticmethod
    def _create_subquery_filter(
            left_id_column,
            right_id_column,
            filter_column,
            filter_factory,
            subquery_decorator=None):
        filter_func = filter_factory(filter_column)
        def wrapper(query, criterion, negated):
            subquery = db.session.query(right_id_column.label('foreign_id'))
            if subquery_decorator:
                subquery = subquery_decorator(subquery)
            subquery = subquery.options(sqlalchemy.orm.lazyload('*'))
            subquery = filter_func(subquery, criterion, negated)
            subquery = subquery.subquery('t')
            return query.filter(left_id_column.in_(subquery))
        return wrapper
