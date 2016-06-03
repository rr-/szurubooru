class AnonymousToken(object):
    def __init__(self, criterion, negated):
        self.criterion = criterion
        self.negated = negated

class NamedToken(AnonymousToken):
    def __init__(self, name, criterion, negated):
        super().__init__(criterion, negated)
        self.name = name

class SortToken(object):
    SORT_DESC = 'desc'
    SORT_ASC = 'asc'
    SORT_DEFAULT = 'default'
    SORT_NEGATED_DEFAULT = 'negated default'

    def __init__(self, name, direction):
        self.name = name
        self.direction = direction

class SpecialToken(object):
    def __init__(self, value, negated):
        self.value = value
        self.negated = negated
