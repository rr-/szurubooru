''' Exports BaseSearchConfig. '''

import sqlalchemy
from szurubooru.errors import SearchError
from szurubooru.services.search.criteria import *
from szurubooru.util import parse_time_range

def _apply_criterion_to_column(
    column, query, criterion, allow_composite=True, allow_ranged=True):
    ''' Decorates SQLAlchemy filter on given column using supplied criterion. '''
    if isinstance(criterion, StringSearchCriterion):
        filter = column == criterion.value
        if criterion.negated:
            filter = ~filter
        return query.filter(filter)
    elif isinstance(criterion, ArraySearchCriterion):
        if not allow_composite:
            raise SearchError(
                'Composite token %r is invalid in this context.' % (criterion,))
        filter = column.in_(criterion.values)
        if criterion.negated:
            filter = ~filter
        return query.filter(filter)
    elif isinstance(criterion, RangedSearchCriterion):
        if not allow_ranged:
            raise SearchError(
                'Ranged token %r is invalid in this context.' % (criterion,))
        filter = column.between(criterion.min_value, criterion.max_value)
        if criterion.negated:
            filter = ~filter
        return query.filter(filter)
    else:
        raise RuntimeError('Invalid search type: %r.' % (criterion,))

def _apply_date_criterion_to_column(column, query, criterion):
    '''
    Decorates SQLAlchemy filter on given column using supplied criterion.
    Parses the datetime inside the criterion.
    '''
    if isinstance(criterion, StringSearchCriterion):
        min_date, max_date = parse_time_range(criterion.value)
        filter = column.between(min_date, max_date)
        if criterion.negated:
            filter = ~filter
        return query.filter(filter)
    elif isinstance(criterion, ArraySearchCriterion):
        result = query
        filter = sqlalchemy.sql.false()
        for value in criterion.values:
            min_date, max_date = parse_time_range(value)
            filter = filter | column.between(min_date, max_date)
        if criterion.negated:
            filter = ~filter
        return query.filter(filter)
    elif isinstance(criterion, RangedSearchCriterion):
        assert criterion.min_value or criterion.max_value
        if criterion.min_value and criterion.max_value:
            min_date = parse_time_range(criterion.min_value)[0]
            max_date = parse_time_range(criterion.max_value)[1]
            filter = column.between(min_date, max_date)
        elif criterion.min_value:
            min_date = parse_time_range(criterion.min_value)[0]
            filter = column >= min_date
        elif criterion.max_value:
            max_date = parse_time_range(criterion.max_value)[1]
            filter = column <= max_date
        if criterion.negated:
            filter = ~filter
        return query.filter(filter)

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
