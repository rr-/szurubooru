from szurubooru import search
from szurubooru.func import auth, snapshots
from szurubooru.rest import routes


_search_executor = search.Executor(search.configs.SnapshotSearchConfig())


@routes.get('/snapshots/?')
def get_snapshots(ctx, _params=None):
    auth.verify_privilege(ctx.user, 'snapshots:list')
    return _search_executor.execute_and_serialize(
        ctx, snapshots.serialize_snapshot)
