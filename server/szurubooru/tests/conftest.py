from datetime import datetime
import pytest
import sqlalchemy
from szurubooru import db, config
from szurubooru.util import misc

@pytest.fixture
def session():
    engine = sqlalchemy.create_engine('sqlite:///:memory:')
    session_maker = sqlalchemy.orm.sessionmaker(bind=engine)
    session_instance = sqlalchemy.orm.scoped_session(session_maker)
    db.Base.query = session_instance.query_property()
    db.Base.metadata.create_all(bind=engine)
    return session_instance

@pytest.fixture
def context_factory(session):
    def factory(request=None, params=None, files=None, user=None):
        params = params or {}
        def get_param_as_string(key, default=None, required=False):
            if key not in params:
                if required:
                    raise RuntimeError('Param is missing!')
                return default
            return params[key]
        def get_param_as_int(key, default=None, required=False):
            if key not in params:
                if required:
                    raise RuntimeError('Param is missing!')
                return default
            return int(params[key])
        context = misc.dotdict()
        context.session = session
        context.request = request or {}
        context.files = files or {}
        context.user = user or db.User()
        context.get_param_as_string = get_param_as_string
        context.get_param_as_int = get_param_as_int
        return context
    return factory

@pytest.fixture
def config_injector():
    def injector(new_config_content):
        config.config = new_config_content
    return injector

@pytest.fixture
def user_factory():
    def factory(name='dummy', rank='regular_user'):
        user = db.User()
        user.name = name
        user.password = 'dummy'
        user.password_salt = 'dummy'
        user.password_hash = 'dummy'
        user.email = 'dummy'
        user.rank = rank
        user.creation_time = datetime(1997, 1, 1)
        user.avatar_style = db.User.AVATAR_GRAVATAR
        return user
    return factory
