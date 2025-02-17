import os
from datetime import datetime, timedelta
from typing import Dict, Optional

from szurubooru import config, rest
from szurubooru.func import auth, posts, users, util

_cache_time = None  # type: Optional[datetime]
_cache_result = None  # type: Optional[int]


def _get_disk_usage() -> int:
    global _cache_time, _cache_result
    threshold = timedelta(hours=48)
    now = datetime.utcnow()
    if _cache_time and _cache_time > now - threshold:
        assert _cache_result is not None
        return _cache_result
    total_size = 0
    for dir_path, _, file_names in os.walk(config.config["data_dir"]):
        for file_name in file_names:
            file_path = os.path.join(dir_path, file_name)
            try:
                total_size += os.path.getsize(file_path)
            except FileNotFoundError:
                pass
    _cache_time = now
    _cache_result = total_size
    return total_size


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
            "defaultTagBlocklist": config.config["default_tag_blocklist"],
            "defaultTagBlocklistForAnonymous": config.config["default_tag_blocklist_for_anonymous"],
            "enableSafety": config.config["enable_safety"],
            "contactEmail": config.config["contact_email"],
            "canSendMails": bool(config.config["smtp"]["host"]),
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
