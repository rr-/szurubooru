from hashlib import md5
from typing import Dict

from szurubooru import config, errors, rest
from szurubooru.func import auth, mailer, users, versions

MAIL_SUBJECT = "Password reset for {name}"
MAIL_BODY = (
    "You (or someone else) requested to reset your password on {name}.\n"
    "If you wish to proceed, click this link: {url}\n"
    "Otherwise, please ignore this email."
)


@rest.routes.get("/password-reset/(?P<user_name>[^/]+)/?")
def start_password_reset(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    user_name = params["user_name"]
    user = users.get_user_by_name_or_email(user_name)
    if not user.email:
        raise errors.ValidationError(
            "User %r hasn't supplied email. Cannot reset password."
            % (user_name)
        )
    token = auth.generate_authentication_token(user)

    if config.config["domain"]:
        url = config.config["domain"]
    elif "HTTP_ORIGIN" in ctx.env:
        url = ctx.env["HTTP_ORIGIN"].rstrip("/")
    elif "HTTP_REFERER" in ctx.env:
        url = ctx.env["HTTP_REFERER"].rstrip("/")
    else:
        url = ""
    url += "/password-reset/%s:%s" % (user.name, token)

    mailer.send_mail(
        config.config["smtp"]["from"],
        user.email,
        MAIL_SUBJECT.format(name=config.config["name"]),
        MAIL_BODY.format(name=config.config["name"], url=url),
    )

    return {}


def _hash(token: str) -> str:
    return md5(token.encode("utf-8")).hexdigest()


@rest.routes.post("/password-reset/(?P<user_name>[^/]+)/?")
def finish_password_reset(
    ctx: rest.Context, params: Dict[str, str]
) -> rest.Response:
    user_name = params["user_name"]
    user = users.get_user_by_name_or_email(user_name)
    good_token = auth.generate_authentication_token(user)
    token = ctx.get_param_as_string("token")
    if _hash(token) != _hash(good_token):
        raise errors.ValidationError("Invalid password reset token.")
    new_password = users.reset_user_password(user)
    versions.bump_version(user)
    ctx.session.commit()
    return {"password": new_password}
