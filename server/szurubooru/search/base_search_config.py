import sqlalchemy
import szurubooru.errors
from szurubooru.util import misc
from szurubooru.search import criteria

def _apply_criterion_to_column(
        column, query, criterion, allow_composite=True, allow_ranged=True):
    ''' Decorate SQLAlchemy filter on given column using supplied criterion. '''
    if isinstance(criterion, criteria.StringSearchCriterion):
        expr = column == criterion.value
        if criterion.negated:
            expr = ~expr
        return query.filter(expr)
    elif isinstance(criterion, criteria.ArraySearchCriterion):
        if not allow_composite:
            raise szurubooru.errors.SearchError(
                'Composite token %r is invalid in this context.' % (criterion,))
        expr = column.in_(criterion.values)
        if criterion.negated:
            expr = ~expr
        return query.filter(expr)
    elif isinstance(criterion, criteria.RangedSearchCriterion):
        if not allow_ranged:
            raise szurubooru.errors.SearchError(
                'Ranged token %r is invalid in this context.' % (criterion,))
        expr = column.between(criterion.min_value, criterion.max_value)
        if criterion.negated:
            expr = ~expr
        return query.filter(expr)
    else:
        raise RuntimeError('Invalid search type: %r.' % (criterion,))

def _apply_date_criterion_to_column(column, query, criterion):
    '''
    Decorate SQLAlchemy filter on given column using supplied criterion.
    Parse the datetime inside the criterion.
    '''
    if isinstance(criterion, criteria.StringSearchCriterion):
        min_date, max_date = misc.parse_time_range(criterion.value)
        expr = column.between(min_date, max_date)
        if criterion.negated:
            expr = ~expr
        return query.filter(expr)
    elif isinstance(criterion, criteria.ArraySearchCriterion):
        expr = sqlalchemy.sql.false()
        for value in criterion.values:
            min_date, max_date = misc.parse_time_range(value)
            expr = expr | column.between(min_date, max_date)
        if criterion.negated:
            expr = ~expr
        return query.filter(expr)
    elif isinstance(criterion, criteria.RangedSearchCriterion):
        assert criterion.min_value or criterion.max_value
        if criterion.min_value and criterion.max_value:
            min_date = misc.parse_time_range(criterion.min_value)[0]
            max_date = misc.parse_time_range(criterion.max_value)[1]
            expr = column.between(min_date, max_date)
        elif criterion.min_value:
            min_date = misc.parse_time_range(criterion.min_value)[0]
            expr = column >= min_date
        elif criterion.max_value:
            max_date = misc.parse_time_range(criterion.max_value)[1]
            expr = column <= max_date
        if criterion.negated:
            expr = ~expr
        return query.filter(expr)

class BaseSearchConfig(object):
    def create_query(self, session):
        raise NotImplementedError()

    @property
    def anonymous_filter(self):
        raise NotImplementedError()

    @property
    def special_filters(self):
        raise NotImplementedError()

    @property
    def named_filters(self):
        raise NotImplementedError()

    @property
    def order_columns(self):
        raise NotImplementedError()

    def _create_basic_filter(
            self, column, allow_composite=True, allow_ranged=True):
        return lambda query, criterion: _apply_criterion_to_column(
            column, query, criterion, allow_composite, allow_ranged)

    def _create_date_filter(self, column):
        return lambda query, criterion: _apply_date_criterion_to_column(
            column, query, criterion)
