from szurubooru import search
from szurubooru.api.base_api import BaseApi
from szurubooru.func import auth, snapshots

class SnapshotListApi(BaseApi):
    def __init__(self):
        super().__init__()
        self._search_executor = search.SearchExecutor(search.SnapshotSearchConfig())

    def get(self, ctx):
        auth.verify_privilege(ctx.user, 'snapshots:list')
        return self._search_executor.execute_and_serialize(
            ctx, snapshots.serialize_snapshot, 'snapshots')
