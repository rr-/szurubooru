from typing import Callable, Dict, Optional, Type

error_handlers = {}


class BaseHttpError(RuntimeError):
    code = -1
    reason = ""

    def __init__(
        self,
        name: str,
        description: str,
        title: Optional[str] = None,
        extra_fields: Optional[Dict[str, str]] = None,
    ) -> None:
        super().__init__()
        # error name for programmers
        self.name = name
        # error description for humans
        self.description = description
        # short title for humans
        self.title = title or self.reason
        # additional fields for programmers
        self.extra_fields = extra_fields


class HttpBadRequest(BaseHttpError):
    code = 400
    reason = "Bad Request"


class HttpForbidden(BaseHttpError):
    code = 403
    reason = "Forbidden"


class HttpNotFound(BaseHttpError):
    code = 404
    reason = "Not Found"


class HttpNotAcceptable(BaseHttpError):
    code = 406
    reason = "Not Acceptable"


class HttpConflict(BaseHttpError):
    code = 409
    reason = "Conflict"


class HttpMethodNotAllowed(BaseHttpError):
    code = 405
    reason = "Method Not Allowed"


class HttpInternalServerError(BaseHttpError):
    code = 500
    reason = "Internal Server Error"


def handle(
    exception_type: Type[Exception], handler: Callable[[Exception], None]
) -> None:
    error_handlers[exception_type] = handler
