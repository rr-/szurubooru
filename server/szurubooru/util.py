''' Exports dotdict. '''

class dotdict(dict): # pylint: disable=invalid-name
    '''dot.notation access to dictionary attributes'''
    def __getattr__(self, attr):
        return self.get(attr)
    __setattr__ = dict.__setitem__
    __delattr__ = dict.__delitem__
