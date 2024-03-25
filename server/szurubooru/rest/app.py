import cgi
import json
import re
import urllib.parse
from datetime import datetime
from typing import Any, Callable, Dict, Tuple

from szurubooru import db
from szurubooru.func import util
from szurubooru.rest import context, errors, middleware, routes


def _json_serializer(obj: Any) -> str:
    """JSON serializer for objects not serializable by default JSON code"""
    if isinstance(obj, datetime):
        serial = obj.isoformat("T") + "Z"
        return serial
    raise TypeError("Type not serializable")


def _dump_json(obj: Any) -> str:
    return json.dumps(obj, default=_json_serializer, indent=2)


def _get_headers(env: Dict[str, Any]) -> Dict[str, str]:
    headers = {}  # type: Dict[str, str]
    for key, value in env.items():
        if key.startswith("HTTP_"):
            key = util.snake_case_to_upper_train_case(key[5:])
            headers[key] = value
    return headers


def _create_context(env: Dict[str, Any]) -> context.Context:
    method = env["REQUEST_METHOD"]
    path = "/" + env["PATH_INFO"].lstrip("/")
    path = path.encode("latin-1").decode("utf-8")  # PEP-3333
    headers = _get_headers(env)

    files = {}
    params = dict(urllib.parse.parse_qsl(env.get("QUERY_STRING", "")))

    if "multipart" in env.get("CONTENT_TYPE", ""):
        form = cgi.FieldStorage(fp=env["wsgi.input"], environ=env)
        if not form.list:
            raise errors.HttpBadRequest(
                "ValidationError", "No files attached."
            )
        body = form.getvalue("metadata")
        for key in form:
            files[key] = form.getvalue(key)
    else:
        body = env["wsgi.input"].read()

    if body:
        try:
            if isinstance(body, bytes):
                body = body.decode("utf-8")

            for key, value in json.loads(body).items():
                params[key] = value
        except (ValueError, UnicodeDecodeError):
            raise errors.HttpBadRequest(
                "ValidationError",
                "Could not decode the request body. The JSON "
                "was incorrect or was not encoded as UTF-8.",
            )

    return context.Context(env, method, path, headers, params, files)


def application(
    env: Dict[str, Any], start_response: Callable[[str, Any], Any]
) -> Tuple[bytes]:
    try:
        ctx = _create_context(env)
        accept_header = ctx.get_header("Accept")
        if "*/*" not in accept_header and "application/json" not in accept_header:
            raise errors.HttpNotAcceptable(
                "ValidationError", "This API only supports JSON responses."
            )

        for url, allowed_methods in routes.routes.items():
            match = re.fullmatch(url, ctx.url)
            if match:
                if ctx.method not in allowed_methods:
                    raise errors.HttpMethodNotAllowed(
                        "ValidationError",
                        "Allowed methods: %r" % allowed_methods,
                    )
                handler = allowed_methods[ctx.method]
                break
        else:
            raise errors.HttpNotFound(
                "ValidationError",
                "Requested path " + ctx.url + " was not found.",
            )

        try:
            ctx.session = db.session()
            try:
                for hook in middleware.pre_hooks:
                    hook(ctx)
                try:
                    response = handler(ctx, match.groupdict())
                except Exception:
                    ctx.session.rollback()
                    raise
                finally:
                    for hook in middleware.post_hooks:
                        hook(ctx)
            finally:
                db.session.remove()

            if type(response) == dict and response.get("return_type") == "custom":
                start_response(response.get("status_code", "200"), [("content-type", "text/html")])
                return (response.get("content", "").encode("utf-8"),)

            start_response("200", [("content-type", "application/json")])
            return (_dump_json(response).encode("utf-8"),)

        except Exception as ex:
            for exception_type, ex_handler in errors.error_handlers.items():
                if isinstance(ex, exception_type):
                    ex_handler(ex)
            raise

    except errors.BaseHttpError as ex:
        start_response(
            "%d %s" % (ex.code, ex.reason),
            [("content-type", "application/json")],
        )
        blob = {
            "name": ex.name,
            "title": ex.title,
            "description": ex.description,
        }
        if ex.extra_fields is not None:
            for key, value in ex.extra_fields.items():
                blob[key] = value
        return (_dump_json(blob).encode("utf-8"),)
