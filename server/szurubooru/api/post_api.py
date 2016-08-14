import datetime
from szurubooru import search
from szurubooru.rest import routes
from szurubooru.func import (
    auth, tags, posts, snapshots, favorites, scores, util)


_search_executor = search.Executor(search.configs.PostSearchConfig())


def _serialize_post(ctx, post):
    return posts.serialize_post(
        post,
        ctx.user,
        options=util.get_serialization_options(ctx))


@routes.get('/posts/?')
def get_posts(ctx, _params=None):
    auth.verify_privilege(ctx.user, 'posts:list')
    _search_executor.config.user = ctx.user
    return _search_executor.execute_and_serialize(
        ctx, lambda post: _serialize_post(ctx, post))


@routes.post('/posts/?')
def create_post(ctx, _params=None):
    anonymous = ctx.get_param_as_bool('anonymous', default=False)
    if anonymous:
        auth.verify_privilege(ctx.user, 'posts:create:anonymous')
    else:
        auth.verify_privilege(ctx.user, 'posts:create:identified')
    content = ctx.get_file('content', required=True)
    tag_names = ctx.get_param_as_list('tags', required=True)
    safety = ctx.get_param_as_string('safety', required=True)
    source = ctx.get_param_as_string('source', required=False, default=None)
    if ctx.has_param('contentUrl') and not source:
        source = ctx.get_param_as_string('contentUrl')
    relations = ctx.get_param_as_list('relations', required=False) or []
    notes = ctx.get_param_as_list('notes', required=False) or []
    flags = ctx.get_param_as_list('flags', required=False) or []

    post, new_tags = posts.create_post(
        content, tag_names, None if anonymous else ctx.user)
    if len(new_tags):
        auth.verify_privilege(ctx.user, 'tags:create')
    posts.update_post_safety(post, safety)
    posts.update_post_source(post, source)
    posts.update_post_relations(post, relations)
    posts.update_post_notes(post, notes)
    posts.update_post_flags(post, flags)
    if ctx.has_file('thumbnail'):
        posts.update_post_thumbnail(post, ctx.get_file('thumbnail'))
    ctx.session.add(post)
    snapshots.save_entity_creation(post, ctx.user)
    ctx.session.commit()
    tags.export_to_json()
    return _serialize_post(ctx, post)


@routes.get('/post/(?P<post_id>[^/]+)/?')
def get_post(ctx, params):
    auth.verify_privilege(ctx.user, 'posts:view')
    post = posts.get_post_by_id(params['post_id'])
    return _serialize_post(ctx, post)


@routes.put('/post/(?P<post_id>[^/]+)/?')
def update_post(ctx, params):
    post = posts.get_post_by_id(params['post_id'])
    util.verify_version(post, ctx)
    if ctx.has_file('content'):
        auth.verify_privilege(ctx.user, 'posts:edit:content')
        posts.update_post_content(post, ctx.get_file('content'))
    if ctx.has_param('tags'):
        auth.verify_privilege(ctx.user, 'posts:edit:tags')
        new_tags = posts.update_post_tags(post, ctx.get_param_as_list('tags'))
        if len(new_tags):
            auth.verify_privilege(ctx.user, 'tags:create')
    if ctx.has_param('safety'):
        auth.verify_privilege(ctx.user, 'posts:edit:safety')
        posts.update_post_safety(post, ctx.get_param_as_string('safety'))
    if ctx.has_param('source'):
        auth.verify_privilege(ctx.user, 'posts:edit:source')
        posts.update_post_source(post, ctx.get_param_as_string('source'))
    elif ctx.has_param('contentUrl'):
        posts.update_post_source(post, ctx.get_param_as_string('contentUrl'))
    if ctx.has_param('relations'):
        auth.verify_privilege(ctx.user, 'posts:edit:relations')
        posts.update_post_relations(post, ctx.get_param_as_list('relations'))
    if ctx.has_param('notes'):
        auth.verify_privilege(ctx.user, 'posts:edit:notes')
        posts.update_post_notes(post, ctx.get_param_as_list('notes'))
    if ctx.has_param('flags'):
        auth.verify_privilege(ctx.user, 'posts:edit:flags')
        posts.update_post_flags(post, ctx.get_param_as_list('flags'))
    if ctx.has_file('thumbnail'):
        auth.verify_privilege(ctx.user, 'posts:edit:thumbnail')
        posts.update_post_thumbnail(post, ctx.get_file('thumbnail'))
    util.bump_version(post)
    post.last_edit_time = datetime.datetime.utcnow()
    ctx.session.flush()
    snapshots.save_entity_modification(post, ctx.user)
    ctx.session.commit()
    tags.export_to_json()
    return _serialize_post(ctx, post)


@routes.delete('/post/(?P<post_id>[^/]+)/?')
def delete_post(ctx, params):
    auth.verify_privilege(ctx.user, 'posts:delete')
    post = posts.get_post_by_id(params['post_id'])
    util.verify_version(post, ctx)
    snapshots.save_entity_deletion(post, ctx.user)
    posts.delete(post)
    ctx.session.commit()
    tags.export_to_json()
    return {}


@routes.get('/featured-post/?')
def get_featured_post(ctx, _params=None):
    post = posts.try_get_featured_post()
    return _serialize_post(ctx, post)


@routes.post('/featured-post/?')
def set_featured_post(ctx, _params=None):
    auth.verify_privilege(ctx.user, 'posts:feature')
    post_id = ctx.get_param_as_int('id', required=True)
    post = posts.get_post_by_id(post_id)
    featured_post = posts.try_get_featured_post()
    if featured_post and featured_post.post_id == post.post_id:
        raise posts.PostAlreadyFeaturedError(
            'Post %r is already featured.' % post_id)
    posts.feature_post(post, ctx.user)
    if featured_post:
        snapshots.save_entity_modification(featured_post, ctx.user)
    snapshots.save_entity_modification(post, ctx.user)
    ctx.session.commit()
    return _serialize_post(ctx, post)


@routes.put('/post/(?P<post_id>[^/]+)/score/?')
def set_post_score(ctx, params):
    auth.verify_privilege(ctx.user, 'posts:score')
    post = posts.get_post_by_id(params['post_id'])
    score = ctx.get_param_as_int('score', required=True)
    scores.set_score(post, ctx.user, score)
    ctx.session.commit()
    return _serialize_post(ctx, post)


@routes.delete('/post/(?P<post_id>[^/]+)/score/?')
def delete_post_score(ctx, params):
    auth.verify_privilege(ctx.user, 'posts:score')
    post = posts.get_post_by_id(params['post_id'])
    scores.delete_score(post, ctx.user)
    ctx.session.commit()
    return _serialize_post(ctx, post)


@routes.post('/post/(?P<post_id>[^/]+)/favorite/?')
def add_post_to_favorites(ctx, params):
    auth.verify_privilege(ctx.user, 'posts:favorite')
    post = posts.get_post_by_id(params['post_id'])
    favorites.set_favorite(post, ctx.user)
    ctx.session.commit()
    return _serialize_post(ctx, post)


@routes.delete('/post/(?P<post_id>[^/]+)/favorite/?')
def delete_post_from_favorites(ctx, params):
    auth.verify_privilege(ctx.user, 'posts:favorite')
    post = posts.get_post_by_id(params['post_id'])
    favorites.unset_favorite(post, ctx.user)
    ctx.session.commit()
    return _serialize_post(ctx, post)


@routes.get('/post/(?P<post_id>[^/]+)/around/?')
def get_posts_around(ctx, params):
    auth.verify_privilege(ctx.user, 'posts:list')
    _search_executor.config.user = ctx.user
    return _search_executor.get_around_and_serialize(
        ctx, params['post_id'], lambda post: _serialize_post(ctx, post))
