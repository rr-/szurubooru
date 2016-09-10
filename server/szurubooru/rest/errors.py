error_handlers = {}  # pylint: disable=invalid-name


class BaseHttpError(RuntimeError):
    code = None
    reason = None

    def __init__(self, description, title=None, extra_fields=None):
        super().__init__()
        self.description = description
        self.title = title or self.reason
        self.extra_fields = extra_fields


class HttpBadRequest(BaseHttpError):
    code = 400
    reason = 'Bad Request'


class HttpForbidden(BaseHttpError):
    code = 403
    reason = 'Forbidden'


class HttpNotFound(BaseHttpError):
    code = 404
    reason = 'Not Found'


class HttpNotAcceptable(BaseHttpError):
    code = 406
    reason = 'Not Acceptable'


class HttpConflict(BaseHttpError):
    code = 409
    reason = 'Conflict'


class HttpMethodNotAllowed(BaseHttpError):
    code = 405
    reason = 'Method Not Allowed'


def handle(exception_type, handler):
    error_handlers[exception_type] = handler
