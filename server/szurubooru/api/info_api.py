import datetime
import os
from szurubooru import config
from szurubooru.api.base_api import BaseApi
from szurubooru.func import posts, users, util

class InfoApi(BaseApi):
    def __init__(self):
        super().__init__()
        self._cache_time = None
        self._cache_result = None

    def get(self, ctx):
        post_feature = posts.try_get_current_post_feature()
        return {
            'postCount': posts.get_post_count(),
            'diskUsage': self._get_disk_usage(),
            'featuredPost': posts.serialize_post(post_feature.post, ctx.user) \
                if post_feature else None,
            'featuringTime': post_feature.time if post_feature else None,
            'featuringUser': users.serialize_user(post_feature.user, ctx.user) \
                if post_feature else None,
            'serverTime': datetime.datetime.now(),
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

    def _get_disk_usage(self):
        threshold = datetime.timedelta(hours=1)
        now = datetime.datetime.now()
        if self._cache_time and self._cache_time > now - threshold:
            return self._cache_result
        total_size = 0
        for dir_path, _, file_names in os.walk(config.config['data_dir']):
            for file_name in file_names:
                file_path = os.path.join(dir_path, file_name)
                total_size += os.path.getsize(file_path)
        self._cache_time = now
        self._cache_result = total_size
        return total_size
