class BaseError(RuntimeError):
    def __init__(self, message='Unknown error', extra_fields=None):
        super().__init__(message)
        self.extra_fields = extra_fields


class ConfigError(BaseError):
    pass


class AuthError(BaseError):
    pass


class IntegrityError(BaseError):
    pass


class ValidationError(BaseError):
    pass


class SearchError(BaseError):
    pass


class NotFoundError(BaseError):
    pass


class ProcessingError(BaseError):
    pass


class MissingRequiredFileError(ValidationError):
    pass


class MissingOrExpiredRequiredFileError(MissingRequiredFileError):
    pass


class MissingRequiredParameterError(ValidationError):
    pass


class InvalidParameterError(ValidationError):
    pass
