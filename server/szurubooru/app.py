''' Exports create_app. '''

import os
import falcon
import sqlalchemy
import sqlalchemy.orm
import szurubooru.api
import szurubooru.config
import szurubooru.middleware
import szurubooru.services
import szurubooru.services.search
import szurubooru.util
from szurubooru.errors import *

class _CustomRequest(falcon.Request):
    context_type = szurubooru.util.dotdict

    def get_param_as_string(self, name, required=False, store=None, default=None):
        params = self._params
        if name in params:
            param = params[name]
            if isinstance(param, list):
                param = ','.join(param)
            if store is not None:
                store[name] = param
            return param
        if not required:
            return default
        raise falcon.HTTPMissingParam(name)

def _on_auth_error(ex, request, response, params):
    raise falcon.HTTPForbidden(
        title='Authentication error', description=str(ex))

def _on_validation_error(ex, request, response, params):
    raise falcon.HTTPBadRequest(title='Validation error', description=str(ex))

def _on_search_error(ex, request, response, params):
    raise falcon.HTTPBadRequest(title='Search error', description=str(ex))

def _on_integrity_error(ex, request, response, params):
    raise falcon.HTTPConflict(
        title='Integrity violation', description=ex.args[0])

def _on_not_found_error(ex, request, response, params):
    raise falcon.HTTPNotFound(title='Not found', description=str(ex))

def create_app():
    ''' Creates a WSGI compatible App object. '''
    config = szurubooru.config.Config()
    root_dir = os.path.dirname(__file__)
    static_dir = os.path.join(root_dir, os.pardir, 'static')

    engine = sqlalchemy.create_engine(
        '{schema}://{user}:{password}@{host}:{port}/{name}'.format(
            schema=config['database']['schema'],
            user=config['database']['user'],
            password=config['database']['pass'],
            host=config['database']['host'],
            port=config['database']['port'],
            name=config['database']['name']))
    session_maker = sqlalchemy.orm.sessionmaker(bind=engine)
    scoped_session = sqlalchemy.orm.scoped_session(session_maker)

    # TODO: is there a better way?
    password_service = szurubooru.services.PasswordService(config)
    auth_service = szurubooru.services.AuthService(config, password_service)
    user_service = szurubooru.services.UserService(config, password_service)

    user_list_api = szurubooru.api.UserListApi(auth_service, user_service)
    user_detail_api = szurubooru.api.UserDetailApi(
        config, auth_service, password_service, user_service)

    app = falcon.API(
        request_type=_CustomRequest,
        middleware=[
            szurubooru.middleware.RequireJson(),
            szurubooru.middleware.JsonTranslator(),
            szurubooru.middleware.DbSession(session_maker),
            szurubooru.middleware.Authenticator(auth_service, user_service),
        ])

    app.add_error_handler(szurubooru.errors.AuthError, _on_auth_error)
    app.add_error_handler(szurubooru.errors.IntegrityError, _on_integrity_error)
    app.add_error_handler(szurubooru.errors.ValidationError, _on_validation_error)
    app.add_error_handler(szurubooru.errors.SearchError, _on_search_error)
    app.add_error_handler(szurubooru.errors.NotFoundError, _on_not_found_error)

    app.add_route('/users/', user_list_api)
    app.add_route('/user/{user_name}', user_detail_api)

    return app
