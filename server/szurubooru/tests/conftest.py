import datetime
import uuid
import pytest
import freezegun
import sqlalchemy
from szurubooru import api, config, db
from szurubooru.util import misc

def get_unique_name():
    return str(uuid.uuid4())

@pytest.fixture
def fake_datetime():
    def injector(now):
        class scope():
            def __enter__(self):
                self.freezer = freezegun.freeze_time(now)
                self.freezer.start()
            def __exit__(self, type, value, trackback):
                self.freezer.stop()
        return scope()
    return injector

@pytest.fixture
def session():
    import logging
    logging.basicConfig()
    logging.getLogger('sqlalchemy.engine').setLevel(logging.INFO)
    engine = sqlalchemy.create_engine('sqlite:///:memory:')
    session_maker = sqlalchemy.orm.sessionmaker(bind=engine)
    session_instance = sqlalchemy.orm.scoped_session(session_maker)
    db.Base.query = session_instance.query_property()
    db.Base.metadata.create_all(bind=engine)
    return session_instance

@pytest.fixture
def context_factory(session):
    def factory(request=None, input=None, files=None, user=None):
        ctx = api.Context()
        ctx.input = input or {}
        ctx.session = session
        ctx.request = request or {}
        ctx.files = files or {}
        ctx.user = user or db.User()
        return ctx
    return factory

@pytest.fixture
def config_injector():
    def injector(new_config_content):
        config.config = new_config_content
    return injector

@pytest.fixture
def user_factory():
    def factory(name=None, rank='regular_user', email='dummy'):
        user = db.User()
        user.name = name or get_unique_name()
        user.password_salt = 'dummy'
        user.password_hash = 'dummy'
        user.email = email
        user.rank = rank
        user.creation_time = datetime.datetime(1997, 1, 1)
        user.avatar_style = db.User.AVATAR_GRAVATAR
        return user
    return factory

@pytest.fixture
def tag_factory():
    def factory(names=None, category='dummy'):
        tag = db.Tag()
        tag.names = [db.TagName(name) for name in (names or [get_unique_name()])]
        tag.category = category
        tag.creation_time = datetime.datetime(1996, 1, 1)
        return tag
    return factory

@pytest.fixture
def post_factory():
    def factory(
            safety=db.Post.SAFETY_SAFE,
            type=db.Post.TYPE_IMAGE,
            checksum='...'):
        post = db.Post()
        post.safety = safety
        post.type = type
        post.checksum = checksum
        post.flags = 0
        post.creation_time = datetime.datetime(1996, 1, 1)
        return post
    return factory
