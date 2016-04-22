import contextlib
import datetime
import uuid
import pytest
import freezegun
import sqlalchemy
from szurubooru import api, config, db
from szurubooru.func import util

class QueryCounter(object):
    def __init__(self):
        self._statements = []
    def __enter__(self):
        self._statements = []
    def __exit__(self, *args, **kwargs):
        self._statements = []
    def create_before_cursor_execute(self):
        def before_cursor_execute(
                _conn, _cursor, statement, _parameters, _context, _executemany):
            self._statements.append(statement)
        return before_cursor_execute
    @property
    def statements(self):
        return self._statements

_query_counter = QueryCounter()
engine = sqlalchemy.create_engine('sqlite:///:memory:')
db.Base.metadata.create_all(bind=engine)
sqlalchemy.event.listen(
    engine,
    'before_cursor_execute',
    _query_counter.create_before_cursor_execute())


def get_unique_name():
    return str(uuid.uuid4())

@pytest.fixture
def fake_datetime():
    @contextlib.contextmanager
    def injector(now):
        freezer = freezegun.freeze_time(now)
        freezer.start()
        yield
        freezer.stop()
    return injector

@pytest.fixture()
def query_counter():
    return _query_counter

@pytest.fixture
def query_logger():
    import logging
    logging.basicConfig()
    logging.getLogger('sqlalchemy.engine').setLevel(logging.INFO)

@pytest.yield_fixture(scope='function', autouse=True)
def session(query_logger):
    session_maker = sqlalchemy.orm.sessionmaker(bind=engine)
    session = sqlalchemy.orm.scoped_session(session_maker)
    db.session = session
    try:
        yield session
    finally:
        session.remove()
        for table in reversed(db.Base.metadata.sorted_tables):
            session.execute(table.delete())
        session.commit()

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
def tag_category_factory(session):
    def factory(name='dummy', color='dummy'):
        category = db.TagCategory()
        category.name = name
        category.color = color
        return category
    return factory

@pytest.fixture
def tag_factory(session):
    def factory(names=None, category=None, category_name='dummy'):
        if not category:
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
            id=None,
            safety=db.Post.SAFETY_SAFE,
            type=db.Post.TYPE_IMAGE,
            checksum='...'):
        post = db.Post()
        post.post_id = id
        post.safety = safety
        post.type = type
        post.checksum = checksum
        post.flags = []
        post.creation_time = datetime.datetime(1996, 1, 1)
        return post
    return factory
