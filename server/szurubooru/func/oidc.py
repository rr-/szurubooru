import base64
import hashlib
import hmac
import json
import os
import re
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime
from typing import Dict, Optional, Tuple

import sqlalchemy as sa

from szurubooru import config, db, errors, model
from szurubooru.func import auth, user_tokens


class OidcError(errors.AuthError):
    pass


class OidcNotConfiguredError(errors.NotFoundError):
    pass


def is_enabled() -> bool:
    oidc = config.config.get("oidc", {}) or {}
    return bool(
        oidc.get("client_id")
        and oidc.get("client_secret")
        and oidc.get("issuer")
    )


_discovery_cache: Optional[Dict] = None
_discovery_cache_time: float = 0.0


def _get_discovery() -> Dict:
    global _discovery_cache, _discovery_cache_time
    if _discovery_cache is not None and (time.time() - _discovery_cache_time) < 3600:
        return _discovery_cache

    oidc = config.config.get("oidc", {}) or {}
    issuer = (oidc.get("issuer") or "").rstrip("/")
    if not issuer:
        raise OidcNotConfiguredError("OIDC is not configured.")

    url = issuer + "/.well-known/openid-configuration"
    try:
        with urllib.request.urlopen(url, timeout=10) as resp:
            data = json.loads(resp.read().decode("utf-8"))
    except Exception as ex:
        raise OidcError("Failed to fetch OIDC discovery document: %s" % ex)

    _discovery_cache = data
    _discovery_cache_time = time.time()
    return _discovery_cache


def generate_state() -> str:
    nonce = os.urandom(16).hex()
    ts = str(int(time.time()))
    data = "%s:%s" % (nonce, ts)
    sig = hmac.new(
        config.config["secret"].encode("utf-8"),
        data.encode("utf-8"),
        hashlib.sha256,
    ).hexdigest()
    return "%s:%s" % (data, sig)


def verify_state(state: str) -> bool:
    try:
        last = state.rfind(":")
        if last < 0:
            return False
        data, sig = state[:last], state[last + 1:]
        expected = hmac.new(
            config.config["secret"].encode("utf-8"),
            data.encode("utf-8"),
            hashlib.sha256,
        ).hexdigest()
        if not hmac.compare_digest(expected, sig):
            return False
        ts_str = data.split(":")[-1]
        return abs(int(time.time()) - int(ts_str)) <= 600
    except Exception:
        return False


def get_authorization_url() -> Tuple[str, str]:
    if not is_enabled():
        raise OidcNotConfiguredError("OIDC is not configured.")
    discovery = _get_discovery()
    oidc = config.config.get("oidc", {}) or {}
    state = generate_state()
    params = {
        "response_type": "code",
        "client_id": oidc["client_id"],
        "redirect_uri": oidc.get("redirect_uri") or "",
        "scope": oidc.get("scopes") or "openid email profile",
        "state": state,
    }
    url = (
        discovery["authorization_endpoint"]
        + "?"
        + urllib.parse.urlencode(params)
    )
    return url, state


def _post_form(url: str, data: Dict) -> Dict:
    body = urllib.parse.urlencode(data).encode("ascii")
    req = urllib.request.Request(
        url,
        data=body,
        headers={"Content-Type": "application/x-www-form-urlencoded"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            return json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as ex:
        body_text = ex.read().decode("utf-8", errors="replace")
        raise OidcError(
            "Token endpoint returned HTTP %d: %s" % (ex.code, body_text[:300])
        )
    except Exception as ex:
        raise OidcError("Token request failed: %s" % ex)


def _decode_id_token(token: str, oidc: Dict) -> Dict:
    try:
        parts = token.split(".")
        if len(parts) != 3:
            raise ValueError("not a 3-part JWT")
        remainder = len(parts[1]) % 4
        padded = parts[1] + ("=" * (4 - remainder) if remainder else "")
        claims = json.loads(
            base64.urlsafe_b64decode(padded).decode("utf-8")
        )
    except Exception as ex:
        raise OidcError("Failed to decode ID token: %s" % ex)

    issuer = (oidc.get("issuer") or "").rstrip("/")
    token_iss = (claims.get("iss") or "").rstrip("/")
    if token_iss != issuer:
        raise OidcError(
            "ID token issuer mismatch: expected %r, got %r" % (issuer, token_iss)
        )

    aud = claims.get("aud")
    client_id = oidc.get("client_id") or ""
    aud_list = [aud] if isinstance(aud, str) else (aud or [])
    if client_id not in aud_list:
        raise OidcError("ID token audience does not include this client.")

    if int(claims.get("exp") or 0) < int(time.time()):
        raise OidcError("ID token has expired.")

    return claims


def _fetch_avatar(url: str) -> Optional[bytes]:
    try:
        req = urllib.request.Request(
            url, headers={"User-Agent": "szurubooru"}
        )
        with urllib.request.urlopen(req, timeout=10) as resp:
            return resp.read()
    except Exception:
        return None


def _sanitize_username(name: str) -> str:
    name = re.sub(r"[^a-zA-Z0-9_-]", "_", name)
    name = name[:32].strip("_")
    return name or "user"


def _find_or_create_user(
    subject: str, display_name: str, email: str, avatar_url: str
) -> model.User:
    from szurubooru.func import users, util

    user = (
        db.session.query(model.User)
        .filter(model.User.oidc_subject == subject)
        .one_or_none()
    )
    if user:
        return user

    if email:
        user = (
            db.session.query(model.User)
            .filter(sa.func.lower(model.User.email) == email.lower())
            .one_or_none()
        )
        if user:
            user.oidc_subject = subject
            return user

    # --- new user from here on ---

    oidc = config.config.get("oidc", {}) or {}
    # PyYAML parses yes/no as True/False booleans
    if not oidc.get("allow_registration", True):
        raise OidcError(
            "No account is linked to this identity. "
            "Contact an administrator to link your account."
        )

    base = (
        _sanitize_username(display_name)
        if display_name
        else (_sanitize_username(email.split("@")[0]) if email else "user")
    )

    name = base
    counter = 1
    while users.try_get_user_by_name(name):
        name = "%s%d" % (base, counter)
        counter += 1

    user = model.User()
    user.name = name
    user.password_hash = "!"
    user.password_salt = ""
    user.password_revision = 0
    user.email = email or None
    user.oidc_subject = subject
    user.rank = (
        model.User.RANK_ADMINISTRATOR
        if users.get_user_count() == 0
        else util.flip(auth.RANK_MAP)[config.config["default_rank"]]
    )
    user.creation_time = datetime.utcnow()
    user.avatar_style = model.User.AVATAR_GRAVATAR
    db.session.add(user)

    if avatar_url:
        avatar_bytes = _fetch_avatar(avatar_url)
        if avatar_bytes:
            try:
                users.update_user_avatar(user, "manual", avatar_bytes)
            except Exception:
                pass  # fall back to gravatar silently

    return user


def exchange_code(
    code: str, state: str
) -> Tuple[model.User, model.UserToken]:
    if not is_enabled():
        raise OidcNotConfiguredError("OIDC is not configured.")
    if not verify_state(state):
        raise OidcError("Invalid or expired OIDC state parameter.")

    discovery = _get_discovery()
    oidc = config.config.get("oidc", {}) or {}

    token_data = _post_form(
        discovery["token_endpoint"],
        {
            "grant_type": "authorization_code",
            "code": code,
            "redirect_uri": oidc.get("redirect_uri") or "",
            "client_id": oidc["client_id"],
            "client_secret": oidc["client_secret"],
        },
    )

    if "error" in token_data:
        raise OidcError(
            "OIDC provider error: %s"
            % token_data.get("error_description", token_data["error"])
        )

    id_token = token_data.get("id_token")
    if not id_token:
        raise OidcError("OIDC provider did not return an id_token.")

    claims = _decode_id_token(id_token, oidc)

    subject = claims.get("sub")
    if not subject:
        raise OidcError("ID token is missing the 'sub' claim.")

    email = claims.get("email") or ""
    display_name = (
        claims.get("preferred_username")
        or claims.get("nickname")
        or claims.get("name")
        or ""
    )
    avatar_claim = (oidc.get("avatar_claim") or "picture") or "picture"
    avatar_url = str(claims.get(avatar_claim) or "")

    user = _find_or_create_user(
        str(subject), str(display_name), str(email), avatar_url
    )

    token = user_tokens.create_user_token(user, enabled=True)
    token.note = "OIDC Login"
    oidc_expiry = oidc.get("expires")
    if oidc_expiry is None:
        oidc_expiry = config.config.get("token_expiry") or 0
    user_tokens.apply_expiry_seconds(token, int(oidc_expiry))
    db.session.add(token)
    user.last_login_time = datetime.utcnow()
    db.session.commit()
    return user, token
