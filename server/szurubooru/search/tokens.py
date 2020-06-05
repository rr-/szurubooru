from szurubooru.search.criteria import BaseCriterion


class AnonymousToken:
    def __init__(self, criterion: BaseCriterion, negated: bool) -> None:
        self.criterion = criterion
        self.negated = negated

    def __hash__(self) -> int:
        return hash((self.criterion, self.negated))


class NamedToken(AnonymousToken):
    def __init__(
        self, name: str, criterion: BaseCriterion, negated: bool
    ) -> None:
        super().__init__(criterion, negated)
        self.name = name

    def __hash__(self) -> int:
        return hash((self.name, self.criterion, self.negated))


class SortToken:
    SORT_DESC = "desc"
    SORT_ASC = "asc"
    SORT_NONE = ""
    SORT_DEFAULT = "default"
    SORT_NEGATED_DEFAULT = "negated default"

    def __init__(self, name: str, order: str) -> None:
        self.name = name
        self.order = order

    def __hash__(self) -> int:
        return hash((self.name, self.order))


class SpecialToken:
    def __init__(self, value: str, negated: bool) -> None:
        self.value = value
        self.negated = negated

    def __hash__(self) -> int:
        return hash((self.value, self.negated))
