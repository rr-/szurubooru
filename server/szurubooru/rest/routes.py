from collections import defaultdict


routes = defaultdict(dict)  # pylint: disable=invalid-name


def get(url):
    def wrapper(handler):
        routes[url]['GET'] = handler
        return handler
    return wrapper


def put(url):
    def wrapper(handler):
        routes[url]['PUT'] = handler
        return handler
    return wrapper


def post(url):
    def wrapper(handler):
        routes[url]['POST'] = handler
        return handler
    return wrapper


def delete(url):
    def wrapper(handler):
        routes[url]['DELETE'] = handler
        return handler
    return wrapper
