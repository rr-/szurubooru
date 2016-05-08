''' Exports create_app. '''

import logging
import coloredlogs
import falcon
from szurubooru import api, config, errors, middleware

def _on_auth_error(ex, _request, _response, _params):
    raise falcon.HTTPForbidden(
        title='Authentication error', description=str(ex))

def _on_validation_error(ex, _request, _response, _params):
    raise falcon.HTTPBadRequest(title='Validation error', description=str(ex))

def _on_search_error(ex, _request, _response, _params):
    raise falcon.HTTPBadRequest(title='Search error', description=str(ex))

def _on_integrity_error(ex, _request, _response, _params):
    raise falcon.HTTPConflict(
        title='Integrity violation', description=ex.args[0])

def _on_not_found_error(ex, _request, _response, _params):
    raise falcon.HTTPNotFound(title='Not found', description=str(ex))

def _on_processing_error(ex, _request, _response, _params):
    raise falcon.HTTPBadRequest(title='Processing error', description=str(ex))

def create_method_not_allowed(allowed_methods):
    allowed = ', '.join(allowed_methods)
    def method_not_allowed(request, response, **_kwargs):
        response.status = falcon.status_codes.HTTP_405
        response.set_header('Allow', allowed)
        request.context.output = {
            'title': 'Method not allowed',
            'description': 'Allowed methods: %r' % allowed_methods,
        }
    return method_not_allowed

def create_app():
    ''' Create a WSGI compatible App object. '''
    falcon.responders.create_method_not_allowed = create_method_not_allowed

    coloredlogs.install(fmt='[%(asctime)-15s] %(name)s %(message)s')
    if config.config['debug']:
        logging.getLogger('szurubooru').setLevel(logging.INFO)
        logging.getLogger('sqlalchemy.engine').setLevel(logging.INFO)

    app = falcon.API(
        request_type=api.Request,
        middleware=[
            middleware.RequireJson(),
            middleware.ContextAdapter(),
            middleware.DbSession(),
            middleware.Authenticator(),
        ])

    app.add_error_handler(errors.AuthError, _on_auth_error)
    app.add_error_handler(errors.IntegrityError, _on_integrity_error)
    app.add_error_handler(errors.ValidationError, _on_validation_error)
    app.add_error_handler(errors.SearchError, _on_search_error)
    app.add_error_handler(errors.NotFoundError, _on_not_found_error)
    app.add_error_handler(errors.ProcessingError, _on_processing_error)

    app.add_route('/users/', api.UserListApi())
    app.add_route('/user/{user_name}', api.UserDetailApi())
    app.add_route('/password-reset/{user_name}', api.PasswordResetApi())

    app.add_route('/tag-categories/', api.TagCategoryListApi())
    app.add_route('/tag-category/{category_name}', api.TagCategoryDetailApi())
    app.add_route('/tags/', api.TagListApi())
    app.add_route('/tag/{tag_name}', api.TagDetailApi())
    app.add_route('/tag-merge/', api.TagMergeApi())
    app.add_route('/tag-siblings/{tag_name}', api.TagSiblingsApi())

    app.add_route('/posts/', api.PostListApi())
    app.add_route('/post/{post_id}', api.PostDetailApi())
    app.add_route('/post/{post_id}/score', api.PostScoreApi())
    app.add_route('/post/{post_id}/favorite', api.PostFavoriteApi())

    app.add_route('/comments/', api.CommentListApi())
    app.add_route('/comment/{comment_id}', api.CommentDetailApi())
    app.add_route('/comment/{comment_id}/score', api.CommentScoreApi())

    app.add_route('/info/', api.InfoApi())
    app.add_route('/featured-post/', api.PostFeatureApi())
    app.add_route('/snapshots/', api.SnapshotListApi())

    return app
