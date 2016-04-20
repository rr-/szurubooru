''' Falcon-compatible API facades. '''

from szurubooru.api.password_reset_api import PasswordResetApi
from szurubooru.api.user_api import UserListApi, UserDetailApi
from szurubooru.api.tag_api import TagListApi, TagDetailApi, TagMergingApi
from szurubooru.api.tag_category_api import TagCategoryListApi, TagCategoryDetailApi
from szurubooru.api.context import Context, Request
