from typing import Any, Callable, Dict, List

from szurubooru import errors, model, rest


def get_serialization_options(ctx: rest.Context) -> List[str]:
    return ctx.get_param_as_list("fields", default=[])


class BaseSerializer:
    _fields = {}  # type: Dict[str, Callable[[model.Base], Any]]

    def serialize(self, options: List[str]) -> Any:
        field_factories = self._serializers()
        if not options:
            options = list(field_factories.keys())
        ret = {}
        for key in options:
            if key not in field_factories:
                raise errors.ValidationError(
                    "Invalid key: %r. Valid keys: %r."
                    % (key, list(sorted(field_factories.keys())))
                )
            factory = field_factories[key]
            ret[key] = factory()
        return ret

    def _serializers(self) -> Dict[str, Callable[[], Any]]:
        raise NotImplementedError()
