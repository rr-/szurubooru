import os
import threading
from datetime import datetime, timedelta
from typing import Dict, Optional

from szurubooru import config, rest
from szurubooru.func import auth, oidc, posts, users, util

_cache_time = None  # type: Optional[datetime]
_cache_result = None  # type: Optional[int]
_cache_computing: bool = False


def _get_disk_usage() -> int:
    global _cache_time, _cache_result, _cache_computing
    threshold = timedelta(hours=48)
    now = datetime.utcnow()
    if not _cache_computing and (
        not _cache_time or now - _cache_time > threshold
    ):
        threading.Thread(target=_compute_disk_usage, daemon=False).start()
    return _cache_result


def _compute_disk_usage() -> None:
    global _cache_time, _cache_result, _cache_computing
    _cache_computing = True
    total_size = 0
    for dirpath, _, filenames in os.walk(config.config["data_dir"]):
        for f in filenames:
            fp = os.path.join(dirpath, f)
            try:
                total_size += os.path.getsize(fp)
            except FileNotFoundError:
                pass
    _cache_result = total_size
    _cache_time = datetime.utcnow()
    _cache_computing = False


@rest.routes.get("/info/?")
def get_info(ctx: rest.Context, _params: Dict[str, str] = {}) -> rest.Response:
    post_feature = posts.try_get_current_post_feature()
    ret = {
        "postCount": posts.get_post_count(),
        "diskUsage": _get_disk_usage(),
        "serverTime": datetime.utcnow(),
        "config": {
            "name": config.config["name"],
            "userNameRegex": config.config["user_name_regex"],
            "passwordRegex": config.config["password_regex"],
            "tagNameRegex": config.config["tag_name_regex"],
            "tagCategoryNameRegex": config.config["tag_category_name_regex"],
            "defaultUserRank": config.config["default_rank"],
            "enableSafety": config.config["enable_safety"],
            "contactEmail": config.config["contact_email"],
            "canSendMails": bool(config.config["smtp"]["host"]),
            "oidcEnabled": oidc.is_enabled(),
            "oidcButtonLabel": (config.config.get("oidc") or {}).get("button_label") or "Log in with SSO",
            "oidcButtonIcon": (config.config.get("oidc") or {}).get("button_icon") or None,
            "disablePasswordAuth": bool((config.config.get("oidc") or {}).get("disable_password_auth", False)),
            "privileges": util.snake_case_to_lower_camel_case_keys(
                config.config["privileges"]
            ),
        },
    }
    if auth.has_privilege(ctx.user, "posts:view:featured"):
        ret["featuredPost"] = (
            posts.serialize_post(post_feature.post, ctx.user)
            if post_feature
            else None
        )
        ret["featuringUser"] = (
            users.serialize_user(post_feature.user, ctx.user)
            if post_feature
            else None
        )
        ret["featuringTime"] = post_feature.time if post_feature else None
    return ret
