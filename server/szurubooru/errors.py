''' Exports custom errors. '''

class AuthError(RuntimeError):
    ''' Generic authentication error '''

class IntegrityError(RuntimeError):
    ''' Database integrity error (e.g. trying to edit nonexisting resource) '''

class ValidationError(RuntimeError):
    ''' Validation error (e.g. trying to create user with invalid name) '''

class SearchError(RuntimeError):
    ''' Search error (e.g. trying to use special: where it doesn't make sense) '''

class NotFoundError(RuntimeError):
    ''' Error thrown when a resource (usually DB) couldn't be found. '''
