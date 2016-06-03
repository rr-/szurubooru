class _BaseCriterion(object):
    def __init__(self, original_text):
        self.original_text = original_text

    def __repr__(self):
        return self.original_text

class RangedCriterion(_BaseCriterion):
    def __init__(self, original_text, min_value, max_value):
        super().__init__(original_text)
        self.min_value = min_value
        self.max_value = max_value

class PlainCriterion(_BaseCriterion):
    def __init__(self, original_text, value):
        super().__init__(original_text)
        self.value = value

class ArrayCriterion(_BaseCriterion):
    def __init__(self, original_text, values):
        super().__init__(original_text)
        self.values = values
