from typing import Optional, List, Dict
from datetime import datetime
from szurubooru import db, model, search, rest
from szurubooru.func import auth, pools, snapshots, serialization, versions


_search_executor = search.Executor(search.configs.PoolSearchConfig())


def _serialize(ctx: rest.Context, pool: model.Pool) -> rest.Response:
    return pools.serialize_pool(
        pool, options=serialization.get_serialization_options(ctx))


def _get_pool(params: Dict[str, str]) -> model.Pool:
    return pools.get_pool_by_id(params['pool_id'])


# def _create_if_needed(pool_names: List[str], user: model.User) -> None:
#     if not pool_names:
#         return
#     _existing_pools, new_pools = pools.get_or_create_pools_by_names(pool_names)
#     if len(new_pools):
#         auth.verify_privilege(user, 'pools:create')
#     db.session.flush()
#     for pool in new_pools:
#         snapshots.create(pool, user)


@rest.routes.get('/pools/?')
def get_pools(ctx: rest.Context, _params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, 'pools:list')
    return _search_executor.execute_and_serialize(
        ctx, lambda pool: _serialize(ctx, pool))


@rest.routes.post('/pools/?')
def create_pool(
        ctx: rest.Context, _params: Dict[str, str] = {}) -> rest.Response:
    auth.verify_privilege(ctx.user, 'pools:create')

    names = ctx.get_param_as_string_list('names')
    category = ctx.get_param_as_string('category')
    description = ctx.get_param_as_string('description', default='')
    # TODO
    # suggestions = ctx.get_param_as_string_list('suggestions', default=[])
    # implications = ctx.get_param_as_string_list('implications', default=[])

    # _create_if_needed(suggestions, ctx.user)
    # _create_if_needed(implications, ctx.user)

    pool = pools.create_pool(names, category)
    pools.update_pool_description(pool, description)
    ctx.session.add(pool)
    ctx.session.flush()
    snapshots.create(pool, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, pool)


@rest.routes.get('/pool/(?P<pool_id>[^/]+)/?')
def get_pool(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    auth.verify_privilege(ctx.user, 'pools:view')
    pool = _get_pool(params)
    return _serialize(ctx, pool)


@rest.routes.put('/pool/(?P<pool_id>[^/]+)/?')
def update_pool(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    pool = _get_pool(params)
    versions.verify_version(pool, ctx)
    versions.bump_version(pool)
    if ctx.has_param('names'):
        auth.verify_privilege(ctx.user, 'pools:edit:names')
        pools.update_pool_names(pool, ctx.get_param_as_string_list('names'))
    if ctx.has_param('category'):
        auth.verify_privilege(ctx.user, 'pools:edit:category')
        pools.update_pool_category_name(
            pool, ctx.get_param_as_string('category'))
    if ctx.has_param('description'):
        auth.verify_privilege(ctx.user, 'pools:edit:description')
        pools.update_pool_description(
            pool, ctx.get_param_as_string('description'))
    # TODO
    # if ctx.has_param('suggestions'):
    #     auth.verify_privilege(ctx.user, 'pools:edit:suggestions')
    #     suggestions = ctx.get_param_as_string_list('suggestions')
    #     _create_if_needed(suggestions, ctx.user)
    #     pools.update_pool_suggestions(pool, suggestions)
    # if ctx.has_param('implications'):
    #     auth.verify_privilege(ctx.user, 'pools:edit:implications')
    #     implications = ctx.get_param_as_string_list('implications')
    #     _create_if_needed(implications, ctx.user)
    #     pools.update_pool_implications(pool, implications)
    pool.last_edit_time = datetime.utcnow()
    ctx.session.flush()
    snapshots.modify(pool, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, pool)


@rest.routes.delete('/pool/(?P<pool_id>[^/]+)/?')
def delete_pool(ctx: rest.Context, params: Dict[str, str]) -> rest.Response:
    pool = _get_pool(params)
    versions.verify_version(pool, ctx)
    auth.verify_privilege(ctx.user, 'pools:delete')
    snapshots.delete(pool, ctx.user)
    pools.delete(pool)
    ctx.session.commit()
    return {}


@rest.routes.post('/pool-merge/?')
def merge_pools(
        ctx: rest.Context, _params: Dict[str, str] = {}) -> rest.Response:
    source_pool_id = ctx.get_param_as_string('remove')
    target_pool_id = ctx.get_param_as_string('mergeTo')
    source_pool = pools.get_pool_by_id(source_pool_id)
    target_pool = pools.get_pool_by_id(target_pool_id)
    versions.verify_version(source_pool, ctx, 'removeVersion')
    versions.verify_version(target_pool, ctx, 'mergeToVersion')
    versions.bump_version(target_pool)
    auth.verify_privilege(ctx.user, 'pools:merge')
    pools.merge_pools(source_pool, target_pool)
    snapshots.merge(source_pool, target_pool, ctx.user)
    ctx.session.commit()
    return _serialize(ctx, target_pool)
