from collections import defaultdict
from typing import Callable, Dict

from szurubooru.rest.context import Context, Response

RouteHandler = Callable[[Context, Dict[str, str]], Response]
routes = defaultdict(dict)  # type: Dict[str, Dict[str, RouteHandler]]


def get(url: str) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]["GET"] = handler
        return handler

    return wrapper


def put(url: str) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]["PUT"] = handler
        return handler

    return wrapper


def post(url: str) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]["POST"] = handler
        return handler

    return wrapper


def delete(url: str) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]["DELETE"] = handler
        return handler

    return wrapper
