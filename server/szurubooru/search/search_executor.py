import re
import sqlalchemy
from szurubooru import errors
from szurubooru.search import criteria

class SearchExecutor(object):
    '''
    Class for search parsing and execution. Handles plaintext parsing and
    delegates sqlalchemy filter decoration to SearchConfig instances.
    '''

    ORDER_DESC = 1
    ORDER_ASC = 2

    def __init__(self, search_config):
        self._search_config = search_config

    def execute(self, session, query_text, page, page_size):
        '''
        Parse input and return tuple containing total record count and filtered
        entities.
        '''
        filter_query = self._prepare(session, query_text)
        entities = filter_query \
            .offset((page - 1) * page_size).limit(page_size).all()
        count_query = filter_query.statement \
            .with_only_columns([sqlalchemy.func.count()]).order_by(None)
        count = filter_query.session.execute(count_query).scalar()
        return (count, entities)

    def _prepare(self, session, query_text):
        ''' Parse input and return SQLAlchemy query. '''
        query = self._search_config.create_query(session)
        for token in re.split(r'\s+', (query_text or '').lower()):
            if not token:
                continue
            negated = False
            while token[0] == '-':
                token = token[1:]
                negated = not negated

            if ':' in token:
                key, value = token.split(':', 2)
                query = self._handle_key_value(query, key, value, negated)
            else:
                query = self._handle_anonymous(
                    query, self._create_criterion(token, negated))

        return query

    def _handle_key_value(self, query, key, value, negated):
        if key == 'order':
            if value.count(',') == 0:
                order = self.ORDER_ASC
            elif value.count(',') == 1:
                value, order_str = value.split(',')
                if order_str == 'asc':
                    order = self.ORDER_ASC
                elif order_str == 'desc':
                    order = self.ORDER_DESC
                else:
                    raise errors.SearchError(
                        'Unknown search direction: %r.' % order_str)
            else:
                raise errors.SearchError(
                    'Too many commas in order search token.')
            if negated:
                if order == self.ORDER_DESC:
                    order = self.ORDER_ASC
                else:
                    order = self.ORDER_DESC
            return self._handle_order(query, value, order)
        elif key == 'special':
            return self._handle_special(query, value, negated)
        else:
            return self._handle_named(
                query, key, self._create_criterion(value, negated))

    def _handle_anonymous(self, query, criterion):
        if not self._search_config.anonymous_filter:
            raise errors.SearchError(
                'Anonymous tokens are not valid in this context.')
        return self._search_config.anonymous_filter(query, criterion)

    def _handle_named(self, query, key, criterion):
        if key in self._search_config.named_filters:
            return self._search_config.named_filters[key](query, criterion)
        raise errors.SearchError(
            'Unknown named token: %r. Available named tokens: %r.' % (
                key, list(self._search_config.named_filters.keys())))

    def _handle_special(self, query, value, negated):
        if value in self._search_config.special_filters:
            return self._search_config.special_filters[value](
                query, value, negated)
        raise errors.SearchError(
            'Unknown special token: %r. Available special tokens: %r.' % (
                value, list(self._search_config.special_filters.keys())))

    def _handle_order(self, query, value, order):
        if value in self._search_config.order_columns:
            column = self._search_config.order_columns[value]
            if order == self.ORDER_ASC:
                column = column.asc()
            else:
                column = column.desc()
            return query.order_by(column)
        raise errors.SearchError(
            'Unknown search order: %r. Available search orders: %r.' % (
                value, list(self._search_config.order_columns.keys())))

    def _create_criterion(self, value, negated):
        if '..' in value:
            low, high = value.split('..')
            if not low and not high:
                raise errors.SearchError('Empty ranged value')
            return criteria.RangedSearchCriterion(value, negated, low, high)
        if ',' in value:
            return criteria.ArraySearchCriterion(
                value, negated, value.split(','))
        return criteria.StringSearchCriterion(value, negated, value)
