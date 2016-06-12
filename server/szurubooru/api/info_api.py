import datetime
import os
from szurubooru import config
from szurubooru.api.base_api import BaseApi
from szurubooru.func import posts, users

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
