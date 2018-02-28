import logging

from typing import Callable, Dict
from collections import defaultdict
from szurubooru.rest.context import Context, Response


logger = logging.getLogger(__name__)

# pylint: disable=invalid-name
RouteHandler = Callable[[Context, Dict[str, str]], Response]
routes = defaultdict(dict)  # type: Dict[str, Dict[str, RouteHandler]]


def get(url: str) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]['GET'] = handler
        logger.info(
            'Registered [GET] %s (user=%s, queries=%d)',
            url)
        return handler
    return wrapper


def put(url: str) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]['PUT'] = handler
        logger.info(
            'Registered [PUT] %s (user=%s, queries=%d)',
            url)
        return handler
    return wrapper


def post(url: str) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]['POST'] = handler
        logger.info(
            'Registered [POST] %s (user=%s, queries=%d)',
            url)
        return handler
    return wrapper


def delete(url: str) -> Callable[[RouteHandler], RouteHandler]:
    def wrapper(handler: RouteHandler) -> RouteHandler:
        routes[url]['DELETE'] = handler
        logger.info(
            'Registered [DELETE] %s (user=%s, queries=%d)',
            url)
        return handler
    return wrapper
