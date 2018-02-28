from typing import List, Callable
from szurubooru.rest.context import Context


# pylint: disable=invalid-name
pre_hooks = []  # type: List[Callable[[Context], None]]
post_hooks = []  # type: List[Callable[[Context], None]]


def pre_hook(handler: Callable) -> Callable:
    pre_hooks.append(handler)


def post_hook(handler: Callable) -> Callable:
    post_hooks.insert(0, handler)
