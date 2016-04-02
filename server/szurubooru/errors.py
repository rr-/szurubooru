''' Exports custom errors. '''

class AuthError(RuntimeError):
    ''' Generic authentication error '''

class IntegrityError(RuntimeError):
    ''' Database integrity error (e.g. trying to edit nonexisting resource) '''

class ValidationError(RuntimeError):
    ''' Validation error (e.g. trying to create user with invalid name) '''
