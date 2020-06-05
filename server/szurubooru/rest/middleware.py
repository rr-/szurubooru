from typing import Callable, List

from szurubooru.rest.context import Context

pre_hooks = []  # type: List[Callable[[Context], None]]
post_hooks = []  # type: List[Callable[[Context], None]]


def pre_hook(handler: Callable) -> None:
    pre_hooks.append(handler)


def post_hook(handler: Callable) -> None:
    post_hooks.insert(0, handler)
