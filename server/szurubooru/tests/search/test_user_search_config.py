from datetime import datetime
import pytest
from szurubooru import db, errors, search

def mock_user(name):
    user = db.User()
    user.name = name
    user.password = 'dummy'
    user.password_salt = 'dummy'
    user.password_hash = 'dummy'
    user.email = 'dummy'
    user.rank = 'dummy'
    user.creation_time = datetime(1997, 1, 1)
    user.avatar_style = db.User.AVATAR_GRAVATAR
    return user

@pytest.fixture
def executor(session):
    search_config = search.UserSearchConfig()
    return search.SearchExecutor(search_config)

@pytest.fixture
def verify_unpaged(session, executor):
    def verify(input, expected_user_names):
        actual_count, actual_users = executor.execute(
            session, input, page=1, page_size=100)
        actual_user_names = [u.name for u in actual_users]
        assert actual_count == len(expected_user_names)
        assert actual_user_names == expected_user_names
    return verify

# -----------------------------------------------------------------------------
@pytest.mark.parametrize('input,expected_user_names', [
    ('creation-time:2014', ['u1', 'u2']),
    ('creation-date:2014', ['u1', 'u2']),
    ('-creation-time:2014', ['u3']),
    ('-creation-date:2014', ['u3']),
    ('creation-time:2014..2014-06', ['u1', 'u2']),
    ('creation-time:2014-06..2015-01-01', ['u2', 'u3']),
    ('creation-time:2014-06..', ['u2', 'u3']),
    ('creation-time:..2014-06', ['u1', 'u2']),
    ('-creation-time:2014..2014-06', ['u3']),
    ('-creation-time:2014-06..2015-01-01', ['u1']),
    ('creation-date:2014..2014-06', ['u1', 'u2']),
    ('creation-date:2014-06..2015-01-01', ['u2', 'u3']),
    ('creation-date:2014-06..', ['u2', 'u3']),
    ('creation-date:..2014-06', ['u1', 'u2']),
    ('-creation-date:2014..2014-06', ['u3']),
    ('-creation-date:2014-06..2015-01-01', ['u1']),
    ('creation-time:2014-01,2015', ['u1', 'u3']),
    ('creation-date:2014-01,2015', ['u1', 'u3']),
    ('-creation-time:2014-01,2015', ['u2']),
    ('-creation-date:2014-01,2015', ['u2']),
])
def test_filter_by_creation_time(
        verify_unpaged, session, input, expected_user_names):
    user1 = mock_user('u1')
    user2 = mock_user('u2')
    user3 = mock_user('u3')
    user1.creation_time = datetime(2014, 1, 1)
    user2.creation_time = datetime(2014, 6, 1)
    user3.creation_time = datetime(2015, 1, 1)
    session.add_all([user1, user2, user3])
    verify_unpaged(input, expected_user_names)

# -----------------------------------------------------------------------------
@pytest.mark.parametrize('input,expected_user_names', [
    ('name:user1', ['user1']),
    ('name:user2', ['user2']),
    ('name:none', []),
    ('name:', []),
    ('name:*1', ['user1']),
    ('name:*2', ['user2']),
    ('name:*', ['user1', 'user2', 'user3']),
    ('name:u*', ['user1', 'user2', 'user3']),
    ('name:*ser*', ['user1', 'user2', 'user3']),
    ('name:*zer*', []),
    ('name:zer*', []),
    ('name:*zer', []),
    ('-name:user1', ['user2', 'user3']),
    ('-name:user2', ['user1', 'user3']),
    ('name:user1,user2', ['user1', 'user2']),
    ('-name:user1,user3', ['user2']),
])
def test_filter_by_name(session, verify_unpaged, input, expected_user_names):
    session.add(mock_user('user1'))
    session.add(mock_user('user2'))
    session.add(mock_user('user3'))
    verify_unpaged(input, expected_user_names)

# -----------------------------------------------------------------------------
@pytest.mark.parametrize('input,expected_user_names', [
    ('', ['u1', 'u2']),
    ('u1', ['u1']),
    ('u2', ['u2']),
    ('u1,u2', ['u1', 'u2']),
])
def test_anonymous(session, verify_unpaged, input, expected_user_names):
    session.add(mock_user('u1'))
    session.add(mock_user('u2'))
    verify_unpaged(input, expected_user_names)

# -----------------------------------------------------------------------------
@pytest.mark.parametrize('input,expected_user_names', [
    ('creation-time:2014 u1', ['u1']),
    ('creation-time:2014 u2', ['u2']),
    ('creation-time:2016 u2', []),
])
def test_combining_tokens(session, verify_unpaged, input, expected_user_names):
    user1 = mock_user('u1')
    user2 = mock_user('u2')
    user3 = mock_user('u3')
    user1.creation_time = datetime(2014, 1, 1)
    user2.creation_time = datetime(2014, 6, 1)
    user3.creation_time = datetime(2015, 1, 1)
    session.add_all([user1, user2, user3])
    verify_unpaged(input, expected_user_names)

# -----------------------------------------------------------------------------
@pytest.mark.parametrize(
    'page,page_size,expected_total_count,expected_user_names', [
        (1, 1, 2, ['u1']),
        (2, 1, 2, ['u2']),
        (3, 1, 2, []),
        (0, 1, 2, ['u1']),
        (0, 0, 2, ['u1']),
    ])
def test_paging(
        session, executor, page, page_size,
        expected_total_count, expected_user_names):
    session.add(mock_user('u1'))
    session.add(mock_user('u2'))
    actual_count, actual_users = executor.execute(
        session, '', page=page, page_size=page_size)
    actual_user_names = [u.name for u in actual_users]
    assert actual_count == expected_total_count
    assert actual_user_names == expected_user_names

# -----------------------------------------------------------------------------
@pytest.mark.parametrize('input,expected_user_names', [
    ('', ['u1', 'u2']),
    ('order:name', ['u1', 'u2']),
    ('-order:name', ['u2', 'u1']),
    ('order:name,asc', ['u1', 'u2']),
    ('order:name,desc', ['u2', 'u1']),
    ('-order:name,asc', ['u2', 'u1']),
    ('-order:name,desc', ['u1', 'u2']),
])
def test_order_by_name(session, verify_unpaged, input, expected_user_names):
    session.add(mock_user('u2'))
    session.add(mock_user('u1'))
    verify_unpaged(input, expected_user_names)

# -----------------------------------------------------------------------------
@pytest.mark.parametrize('input,expected_error', [
    ('creation-date:..', errors.SearchError),
    ('creation-date:bad..', errors.ValidationError),
    ('creation-date:..bad', errors.ValidationError),
    ('creation-date:bad..bad', errors.ValidationError),
    ('name:a..b', errors.SearchError),
    ('order:', errors.SearchError),
    ('order:nam', errors.SearchError),
    ('order:name,as', errors.SearchError),
    ('order:name,asc,desc', errors.SearchError),
    ('bad:x', errors.SearchError),
    ('special:unsupported', errors.SearchError),
])
def test_bad_tokens(executor, session, input, expected_error):
    with pytest.raises(expected_error):
        executor.execute(session, input, page=1, page_size=100)
