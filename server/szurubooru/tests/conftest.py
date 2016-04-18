import datetime
import uuid
from contextlib import contextmanager
import pytest
import freezegun
import sqlalchemy
from szurubooru import api, config, db
from szurubooru.util import misc

def get_unique_name():
    return str(uuid.uuid4())

@pytest.fixture
def fake_datetime():
    @contextmanager
    def injector(now):
        freezer = freezegun.freeze_time(now)
        freezer.start()
        yield
        freezer.stop()
    return injector

@pytest.yield_fixture
def session(autoload=True):
    import logging
    logging.basicConfig()
    logging.getLogger('sqlalchemy.engine').setLevel(logging.INFO)
    engine = sqlalchemy.create_engine('sqlite:///:memory:')
    session_maker = sqlalchemy.orm.sessionmaker(bind=engine)
    session = sqlalchemy.orm.scoped_session(session_maker)
    db.Base.query = session.query_property()
    db.Base.metadata.create_all(bind=engine)
    db.session = session
    yield session
    session.remove()

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
def tag_factory(session):
    def factory(names=None, category_name='dummy'):
        category = db.TagCategory(category_name)
        session.add(category)
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
