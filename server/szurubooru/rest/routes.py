from collections import defaultdict
from typing import Callable, Dict

from szurubooru.rest.context import Context, Response

RouteHandler = Callable[[Context, Dict[str, str]], Response]
routes = {  # type: Dict[Dict[str, Dict[str, RouteHandler]]]
    "application/json": defaultdict(dict),
    "text/html": defaultdict(dict),
}


def get(
    url: str, accept: str = "application/json"
) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[accept][url]["GET"] = handler
        return handler

    return wrapper


def put(
    url: str, accept: str = "application/json"
) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[accept][url]["PUT"] = handler
        return handler

    return wrapper


def post(
    url: str, accept: str = "application/json"
) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[accept][url]["POST"] = handler
        return handler

    return wrapper


def delete(
    url: str, accept: str = "application/json"
) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[accept][url]["DELETE"] = handler
        return handler

    return wrapper
