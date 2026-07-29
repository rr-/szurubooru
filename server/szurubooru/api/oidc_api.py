from typing import Dict

from szurubooru import rest
from szurubooru.func import oidc


@rest.routes.get("/auth/oidc/?")
def get_oidc_authorization(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    authorization_url, state = oidc.get_authorization_url()
    return {"authorizationUrl": authorization_url, "state": state}


@rest.routes.post("/auth/oidc/?")
def exchange_oidc_code(
    ctx: rest.Context, _params: Dict[str, str] = {}
) -> rest.Response:
    code = ctx.get_param_as_string("code")
    state = ctx.get_param_as_string("state")
    user, token = oidc.exchange_code(code, state)
    return {"userName": user.name, "token": token.token}
