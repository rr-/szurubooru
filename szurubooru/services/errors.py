''' Exports custom errors. '''

class AuthError(RuntimeError):
    ''' Generic authentication error '''
    pass

class IntegrityError(RuntimeError):
    ''' Database integrity error '''
    pass
