import os
import falcon
import sqlalchemy
import sqlalchemy.orm
import szurubooru.rest.users
from szurubooru.config import Config
from szurubooru.middleware import Authenticator, JsonTranslator, RequireJson
from szurubooru.services import AuthService, UserService

def create_app():
    config = Config()
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
    session = sqlalchemy.orm.sessionmaker(bind=engine)()

    user_service = UserService(session)
    auth_service = AuthService(config, user_service)

    user_list = szurubooru.rest.users.UserList(auth_service)
    user = szurubooru.rest.users.User(auth_service)

    app = falcon.API(middleware=[
        RequireJson(),
        JsonTranslator(),
        Authenticator(auth_service),
    ])

    app.add_route('/users/', user_list)
    app.add_route('/user/{user_id}', user)

    return app
