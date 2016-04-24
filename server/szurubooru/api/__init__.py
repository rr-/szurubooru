''' Falcon-compatible API facades. '''

from szurubooru.api.password_reset_api import PasswordResetApi
from szurubooru.api.user_api import UserListApi, UserDetailApi
from szurubooru.api.tag_api import (
    TagListApi,
    TagDetailApi,
    TagMergeApi,
    TagSiblingsApi)
from szurubooru.api.tag_category_api import (
    TagCategoryListApi,
    TagCategoryDetailApi)
from szurubooru.api.comment_api import (
    CommentListApi,
    CommentDetailApi,
    CommentScoreApi)
from szurubooru.api.post_api import (
    PostFeatureApi,
    PostScoreApi)
from szurubooru.api.snapshot_api import SnapshotListApi
from szurubooru.api.info_api import InfoApi
from szurubooru.api.context import Context, Request
