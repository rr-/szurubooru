from typing import Callable, Dict, Optional, Tuple

from szurubooru.search import criteria, tokens
from szurubooru.search.query import SearchQuery
from szurubooru.search.typing import SaColumn, SaQuery

Filter = Callable[[SaQuery, Optional[criteria.BaseCriterion], bool], SaQuery]


class BaseSearchConfig:
    SORT_NONE = tokens.SortToken.SORT_NONE
    SORT_ASC = tokens.SortToken.SORT_ASC
    SORT_DESC = tokens.SortToken.SORT_DESC

    def on_search_query_parsed(self, search_query: SearchQuery) -> None:
        pass

    def create_filter_query(self, _disable_eager_loads: bool) -> SaQuery:
        raise NotImplementedError()

    def create_count_query(self, disable_eager_loads: bool) -> SaQuery:
        raise NotImplementedError()

    def create_around_query(self) -> SaQuery:
        raise NotImplementedError()

    def finalize_query(self, query: SaQuery) -> SaQuery:
        return query

    @property
    def id_column(self) -> SaColumn:
        return None

    @property
    def anonymous_filter(self) -> Optional[Filter]:
        return None

    @property
    def special_filters(self) -> Dict[str, Filter]:
        return {}

    @property
    def named_filters(self) -> Dict[str, Filter]:
        return {}

    @property
    def sort_columns(self) -> Dict[str, Tuple[SaColumn, str]]:
        return {}
