class ConfigError(RuntimeError):
    pass


class AuthError(RuntimeError):
    pass


class IntegrityError(RuntimeError):
    pass


class ValidationError(RuntimeError):
    pass


class SearchError(RuntimeError):
    pass


class NotFoundError(RuntimeError):
    pass


class ProcessingError(RuntimeError):
    pass


class MissingRequiredFileError(ValidationError):
    pass


class MissingRequiredParameterError(ValidationError):
    pass


class InvalidParameterError(ValidationError):
    pass
