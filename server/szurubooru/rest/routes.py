from collections import defaultdict
from typing import Callable, Dict, Tuple

from szurubooru.rest.context import Context, Response

RouteHandler = Callable[[Context, Dict[str, str]], Response]
routes = defaultdict(dict)
# type: Dict[str, Dict[str, Tuple[RouteHandler, str]]]


def get(
    url: str, accept: str = "application/json"
) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]["GET"] = (handler, accept)
        return handler

    return wrapper


def put(
    url: str, accept: str = "application/json"
) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]["PUT"] = (handler, accept)
        return handler

    return wrapper


def post(
    url: str, accept: str = "application/json"
) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]["POST"] = (handler, accept)
        return handler

    return wrapper


def delete(
    url: str, accept: str = "application/json"
) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]["DELETE"] = (handler, accept)
        return handler

    return wrapper
