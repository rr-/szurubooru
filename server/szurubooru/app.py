''' Exports create_app. '''

import falcon
from szurubooru import api, errors, middleware

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

    app = falcon.API(
        request_type=api.Request,
        middleware=[
            middleware.RequireJson(),
            middleware.ContextAdapter(),
            middleware.DbSession(),
            middleware.Authenticator(),
        ])

    user_list_api = api.UserListApi()
    user_detail_api = api.UserDetailApi()
    tag_category_list_api = api.TagCategoryListApi()
    tag_category_detail_api = api.TagCategoryDetailApi()
    tag_list_api = api.TagListApi()
    tag_detail_api = api.TagDetailApi()
    tag_merging_api = api.TagMergingApi()
    password_reset_api = api.PasswordResetApi()

    app.add_error_handler(errors.AuthError, _on_auth_error)
    app.add_error_handler(errors.IntegrityError, _on_integrity_error)
    app.add_error_handler(errors.ValidationError, _on_validation_error)
    app.add_error_handler(errors.SearchError, _on_search_error)
    app.add_error_handler(errors.NotFoundError, _on_not_found_error)
    app.add_error_handler(errors.ProcessingError, _on_processing_error)

    app.add_route('/users/', user_list_api)
    app.add_route('/user/{user_name}', user_detail_api)
    app.add_route('/tag-categories/', tag_category_list_api)
    app.add_route('/tag-category/{category_name}', tag_category_detail_api)
    app.add_route('/tags/', tag_list_api)
    app.add_route('/tag/{tag_name}', tag_detail_api)
    app.add_route('/tag-merge/', tag_merging_api)
    app.add_route('/password-reset/{user_name}', password_reset_api)

    return app
