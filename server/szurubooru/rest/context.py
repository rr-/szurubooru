from typing import Any, Dict, List, Optional, Union, cast

from szurubooru import errors, model
from szurubooru.func import file_uploads, net

MISSING = object()
Request = Dict[str, Any]
Response = Optional[Dict[str, Any]]


class Context:
    def __init__(
        self,
        env: Dict[str, Any],
        method: str,
        url: str,
        headers: Dict[str, str] = None,
        params: Request = None,
        files: Dict[str, bytes] = None,
    ) -> None:
        self.env = env
        self.method = method
        self.url = url
        self._headers = headers or {}
        self._params = params or {}
        self._files = files or {}

        self.user = model.User()
        self.user.name = None
        self.user.rank = "anonymous"

        self.session = None  # type: Any

    def has_header(self, name: str) -> bool:
        return name in self._headers

    def get_header(self, name: str) -> str:
        return self._headers.get(name, "")

    def has_file(self, name: str, allow_tokens: bool = True) -> bool:
        return (
            name in self._files
            or name + "Url" in self._params
            or (allow_tokens and name + "Token" in self._params)
        )

    def get_file(
        self,
        name: str,
        default: Union[object, bytes] = MISSING,
        use_downloader: bool = False,
        allow_tokens: bool = True,
    ) -> bytes:
        if name in self._files and self._files[name]:
            return self._files[name]

        if name + "Url" in self._params:
            return net.download(
                self._params[name + "Url"],
                use_downloader=use_downloader,
            )

        if allow_tokens and name + "Token" in self._params:
            ret = file_uploads.get(self._params[name + "Token"])
            if ret:
                return ret
            elif default is not MISSING:
                raise errors.MissingOrExpiredRequiredFileError(
                    "Required file %r is missing or has expired." % name
                )

        if default is not MISSING:
            return cast(bytes, default)
        raise errors.MissingRequiredFileError(
            "Required file %r is missing." % name
        )

    def has_param(self, name: str) -> bool:
        return name in self._params

    def get_param_as_list(
        self, name: str, default: Union[object, List[Any]] = MISSING
    ) -> List[Any]:
        if name not in self._params:
            if default is not MISSING:
                return cast(List[Any], default)
            raise errors.MissingRequiredParameterError(
                "Required parameter %r is missing." % name
            )
        value = self._params[name]
        if type(value) is str:
            if "," in value:
                return value.split(",")
            return [value]
        if type(value) is list:
            return value
        raise errors.InvalidParameterError(
            "Parameter %r must be a list." % name
        )

    def get_param_as_int_list(
        self, name: str, default: Union[object, List[int]] = MISSING
    ) -> List[int]:
        ret = self.get_param_as_list(name, default)
        for item in ret:
            if type(item) is not int:
                raise errors.InvalidParameterError(
                    "Parameter %r must be a list of integer values." % name
                )
        return ret

    def get_param_as_string_list(
        self, name: str, default: Union[object, List[str]] = MISSING
    ) -> List[str]:
        ret = self.get_param_as_list(name, default)
        for item in ret:
            if type(item) is not str:
                raise errors.InvalidParameterError(
                    "Parameter %r must be a list of string values." % name
                )
        return ret

    def get_param_as_string(
        self, name: str, default: Union[object, str] = MISSING
    ) -> str:
        if name not in self._params:
            if default is not MISSING:
                return cast(str, default)
            raise errors.MissingRequiredParameterError(
                "Required parameter %r is missing." % name
            )
        value = self._params[name]
        try:
            if value is None:
                return ""
            if type(value) is list:
                return ",".join(value)
            if type(value) is int or type(value) is float:
                return str(value)
            if type(value) is str:
                return value
        except TypeError:
            pass
        raise errors.InvalidParameterError(
            "Parameter %r must be a string value." % name
        )

    def get_param_as_int(
        self,
        name: str,
        default: Union[object, int] = MISSING,
        min: Optional[int] = None,
        max: Optional[int] = None,
    ) -> int:
        if name not in self._params:
            if default is not MISSING:
                return cast(int, default)
            raise errors.MissingRequiredParameterError(
                "Required parameter %r is missing." % name
            )
        value = self._params[name]
        try:
            value = int(value)
            if min is not None and value < min:
                raise errors.InvalidParameterError(
                    "Parameter %r must be at least %r." % (name, min)
                )
            if max is not None and value > max:
                raise errors.InvalidParameterError(
                    "Parameter %r may not exceed %r." % (name, max)
                )
            return value
        except (ValueError, TypeError):
            pass
        raise errors.InvalidParameterError(
            "Parameter %r must be an integer value." % name
        )

    def get_param_as_bool(
        self, name: str, default: Union[object, bool] = MISSING
    ) -> bool:
        if name not in self._params:
            if default is not MISSING:
                return cast(bool, default)
            raise errors.MissingRequiredParameterError(
                "Required parameter %r is missing." % name
            )
        value = self._params[name]
        try:
            value = str(value).lower()
        except TypeError:
            pass
        if value in ["1", "y", "yes", "yeah", "yep", "yup", "t", "true"]:
            return True
        if value in ["0", "n", "no", "nope", "f", "false"]:
            return False
        raise errors.InvalidParameterError(
            "Parameter %r must be a boolean value." % name
        )
