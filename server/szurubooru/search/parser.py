import re
from szurubooru import errors
from szurubooru.search import criteria, tokens

def _create_criterion(original_value, value):
    if '..' in value:
        low, high = value.split('..', 1)
        if not low and not high:
            raise errors.SearchError('Empty ranged value')
        return criteria.RangedCriterion(original_value, low, high)
    if ',' in value:
        return criteria.ArrayCriterion(
            original_value, value.split(','))
    return criteria.PlainCriterion(original_value, value)

def _parse_anonymous(value, negated):
    criterion = _create_criterion(value, value)
    return tokens.AnonymousToken(criterion, negated)

def _parse_named(key, value, negated):
    original_value = value
    if key.endswith('-min'):
        key = key[:-4]
        value += '..'
    elif key.endswith('-max'):
        key = key[:-4]
        value = '..' + value
    criterion = _create_criterion(original_value, value)
    return tokens.NamedToken(key, criterion, negated)

def _parse_special(value, negated):
    return tokens.SpecialToken(value, negated)

def _parse_sort(value, negated):
    if value.count(',') == 0:
        direction_str = None
    elif value.count(',') == 1:
        value, direction_str = value.split(',')
    else:
        raise errors.SearchError('Too many commas in sort style token.')
    try:
        direction = {
            'asc': tokens.SortToken.SORT_ASC,
            'desc': tokens.SortToken.SORT_DESC,
            '': tokens.SortToken.SORT_DEFAULT,
            None: tokens.SortToken.SORT_DEFAULT,
        }[direction_str]
    except KeyError:
        raise errors.SearchError(
            'Unknown search direction: %r.' % direction_str)
    if negated:
        direction = {
            tokens.SortToken.SORT_ASC: tokens.SortToken.SORT_DESC,
            tokens.SortToken.SORT_DESC: tokens.SortToken.SORT_ASC,
            tokens.SortToken.SORT_DEFAULT: tokens.SortToken.SORT_NEGATED_DEFAULT,
            tokens.SortToken.SORT_NEGATED_DEFAULT: tokens.SortToken.SORT_DEFAULT,
        }[direction]
    return tokens.SortToken(value, direction)

class SearchQuery():
    def __init__(self):
        self.anonymous_tokens = []
        self.named_tokens = []
        self.special_tokens = []
        self.sort_tokens = []

class Parser(object):
    def parse(self, query_text):
        query = SearchQuery()
        for chunk in re.split(r'\s+', (query_text or '').lower()):
            if not chunk:
                continue
            negated = False
            while chunk[0] == '-':
                chunk = chunk[1:]
                negated = not negated
            if ':' in chunk and chunk[0] != ':':
                key, value = chunk.split(':', 2)
                if key == 'sort':
                    query.sort_tokens.append(
                        _parse_sort(value, negated))
                elif key == 'special':
                    query.special_tokens.append(
                        _parse_special(value, negated))
                else:
                    query.named_tokens.append(
                        _parse_named(key, value, negated))
            else:
                query.anonymous_tokens.append(_parse_anonymous(chunk, negated))
        return query
