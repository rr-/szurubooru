from typing import List

from szurubooru.search import tokens


class SearchQuery:
    def __init__(self) -> None:
        self.anonymous_tokens = []  # type: List[tokens.AnonymousToken]
        self.named_tokens = []  # type: List[tokens.NamedToken]
        self.special_tokens = []  # type: List[tokens.SpecialToken]
        self.sort_tokens = []  # type: List[tokens.SortToken]

    def __hash__(self) -> int:
        return hash(
            (
                tuple(self.anonymous_tokens),
                tuple(self.named_tokens),
                tuple(self.special_tokens),
                tuple(self.sort_tokens),
            )
        )
