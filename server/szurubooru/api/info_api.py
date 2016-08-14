import datetime
import os
from szurubooru import config
from szurubooru.func import posts, users, util
from szurubooru.rest import routes

_cache_time = None
_cache_result = None

def _get_disk_usage():
    global _cache_time, _cache_result # pylint: disable=global-statement
    threshold = datetime.timedelta(hours=1)
    now = datetime.datetime.utcnow()
    if _cache_time and _cache_time > now - threshold:
        return _cache_result
    total_size = 0
    for dir_path, _, file_names in os.walk(config.config['data_dir']):
        for file_name in file_names:
            file_path = os.path.join(dir_path, file_name)
            total_size += os.path.getsize(file_path)
    _cache_time = now
    _cache_result = total_size
    return total_size

@routes.get('/info/?')
def get_info(ctx, _params=None):
    post_feature = posts.try_get_current_post_feature()
    return {
        'postCount': posts.get_post_count(),
        'diskUsage': _get_disk_usage(),
        'featuredPost': posts.serialize_post(post_feature.post, ctx.user) \
            if post_feature else None,
        'featuringTime': post_feature.time if post_feature else None,
        'featuringUser': users.serialize_user(post_feature.user, ctx.user) \
            if post_feature else None,
        'serverTime': datetime.datetime.utcnow(),
        'config': {
            'userNameRegex': config.config['user_name_regex'],
            'passwordRegex': config.config['password_regex'],
            'tagNameRegex': config.config['tag_name_regex'],
            'tagCategoryNameRegex': config.config['tag_category_name_regex'],
            'defaultUserRank': config.config['default_rank'],
            'privileges': util.snake_case_to_lower_camel_case_keys(
                config.config['privileges']),
        },
    }
