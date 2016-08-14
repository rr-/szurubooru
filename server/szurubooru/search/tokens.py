class AnonymousToken(object):
    def __init__(self, criterion, negated):
        self.criterion = criterion
        self.negated = negated

    def __hash__(self):
        return hash((self.criterion, self.negated))


class NamedToken(AnonymousToken):
    def __init__(self, name, criterion, negated):
        super().__init__(criterion, negated)
        self.name = name

    def __hash__(self):
        return hash((self.name, self.criterion, self.negated))


class SortToken(object):
    SORT_DESC = 'desc'
    SORT_ASC = 'asc'
    SORT_DEFAULT = 'default'
    SORT_NEGATED_DEFAULT = 'negated default'

    def __init__(self, name, order):
        self.name = name
        self.order = order

    def __hash__(self):
        return hash((self.name, self.order))


class SpecialToken(object):
    def __init__(self, value, negated):
        self.value = value
        self.negated = negated

    def __hash__(self):
        return hash((self.value, self.negated))
