class _BaseSearchCriterion(object):
    def __init__(self, original_text, negated):
        self.original_text = original_text
        self.negated = negated

    def __repr__(self):
        return self.original_text

class RangedSearchCriterion(_BaseSearchCriterion):
    def __init__(self, original_text, negated, min_value, max_value):
        super().__init__(original_text, negated)
        self.min_value = min_value
        self.max_value = max_value

class PlainSearchCriterion(_BaseSearchCriterion):
    def __init__(self, original_text, negated, value):
        super().__init__(original_text, negated)
        self.value = value

class ArraySearchCriterion(_BaseSearchCriterion):
    def __init__(self, original_text, negated, values):
        super().__init__(original_text, negated)
        self.values = values
