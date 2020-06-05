from typing import List, Optional

from szurubooru.search.typing import SaQuery


class BaseCriterion:
    def __init__(self, original_text: str) -> None:
        self.original_text = original_text

    def __repr__(self) -> str:
        return self.original_text


class RangedCriterion(BaseCriterion):
    def __init__(
        self,
        original_text: str,
        min_value: Optional[str],
        max_value: Optional[str],
    ) -> None:
        super().__init__(original_text)
        self.min_value = min_value
        self.max_value = max_value

    def __hash__(self) -> int:
        return hash(("range", self.min_value, self.max_value))


class PlainCriterion(BaseCriterion):
    def __init__(self, original_text: str, value: str) -> None:
        super().__init__(original_text)
        self.value = value

    def __hash__(self) -> int:
        return hash(self.value)


class ArrayCriterion(BaseCriterion):
    def __init__(self, original_text: str, values: List[str]) -> None:
        super().__init__(original_text)
        self.values = values

    def __hash__(self) -> int:
        return hash(tuple(["array"] + self.values))
