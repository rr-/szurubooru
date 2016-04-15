import pytest
from szurubooru import api, db, errors
from szurubooru.util import auth

@pytest.fixture
def user_detail_api():
    return api.UserDetailApi()

def test_updating_user(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api):
    config_injector({
        'secret': '',
        'user_name_regex': '.{3,}',
        'password_regex': '.{3,}',
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {},
        'privileges': {
            'users:edit:self:name': 'regular_user',
            'users:edit:self:pass': 'regular_user',
            'users:edit:self:email': 'regular_user',
            'users:edit:self:rank': 'mod',
            'users:edit:self:avatar': 'mod',
        },
    })
    user = user_factory(name='u1', rank='admin')
    session.add(user)
    user_detail_api.put(
        context_factory(
            request={
                'name': 'chewie',
                'email': 'asd@asd.asd',
                'password': 'oks',
                'rank': 'mod',
                'avatarStyle': 'gravatar',
            },
            user=user),
        'u1')
    user = session.query(db.User).filter_by(name='chewie').one()
    assert user.name == 'chewie'
    assert user.email == 'asd@asd.asd'
    assert user.rank == 'mod'
    assert user.avatar_style == user.AVATAR_GRAVATAR
    assert auth.is_valid_password(user, 'oks') is True
    assert auth.is_valid_password(user, 'invalid') is False

def test_update_changing_nothing(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api):
    config_injector({
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {},
    })
    user = user_factory(name='u1', rank='admin')
    session.add(user)
    user_detail_api.put(context_factory(user=user), 'u1')
    user = session.query(db.User).filter_by(name='u1').one()
    assert user.name == 'u1'
    assert user.email == 'dummy'
    assert user.rank == 'admin'

def test_updating_non_existing_user(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api):
    config_injector({
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
    })
    user = user_factory(name='u1', rank='admin')
    session.add(user)
    with pytest.raises(errors.NotFoundError):
        user_detail_api.put(context_factory(user=user), 'u2')

def test_removing_email(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api):
    config_injector({
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {},
        'privileges': {'users:edit:self:email': 'regular_user'},
    })
    user = user_factory(name='u1', rank='admin')
    session.add(user)
    user_detail_api.put(
        context_factory(request={'email': ''}, user=user), 'u1')
    assert session.query(db.User).filter_by(name='u1').one().email is None

@pytest.mark.parametrize('request', [
    {'name': '.'},
    {'password': '.'},
    {'rank': '.'},
    {'email': '.'},
    {'avatarStyle': 'manual'},
])
def test_invalid_inputs(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api,
        request):
    config_injector({
        'user_name_regex': '.{3,}',
        'password_regex': '.{3,}',
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'privileges': {
            'users:edit:self:name': 'regular_user',
            'users:edit:self:pass': 'regular_user',
            'users:edit:self:email': 'regular_user',
            'users:edit:self:rank': 'mod',
            'users:edit:self:avatar': 'mod',
        },
    })
    user = user_factory(name='u1', rank='admin')
    session.add(user)
    with pytest.raises(errors.ValidationError):
        user_detail_api.put(context_factory(request=request, user=user), 'u1')

@pytest.mark.parametrize('request', [
    {'name': 'whatever'},
    {'email': 'whatever'},
    {'rank': 'whatever'},
    {'password': 'whatever'},
    {'avatarStyle': 'whatever'},
])
def test_user_trying_to_update_someone_else(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api,
        request):
    config_injector({
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'privileges': {
            'users:edit:any:name': 'mod',
            'users:edit:any:pass': 'mod',
            'users:edit:any:email': 'mod',
            'users:edit:any:rank': 'admin',
            'users:edit:any:avatar': 'admin',
        },
    })
    user1 = user_factory(name='u1', rank='regular_user')
    user2 = user_factory(name='u2', rank='regular_user')
    session.add_all([user1, user2])
    with pytest.raises(errors.AuthError):
        user_detail_api.put(
            context_factory(request=request, user=user1), user2.name)

def test_user_trying_to_become_someone_else(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api):
    config_injector({
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'privileges': {'users:edit:self:name': 'regular_user'},
    })
    user1 = user_factory(name='me', rank='regular_user')
    user2 = user_factory(name='her', rank='regular_user')
    session.add_all([user1, user2])
    with pytest.raises(errors.IntegrityError):
        user_detail_api.put(
            context_factory(request={'name': 'her'}, user=user1),
            'me')
    with pytest.raises(errors.IntegrityError):
        user_detail_api.put(
            context_factory(request={'name': 'HER'}, user=user1), 'me')

def test_mods_trying_to_become_admin(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api):
    config_injector({
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'privileges': {
            'users:edit:self:rank': 'mod',
            'users:edit:any:rank': 'admin',
        },
    })
    user1 = user_factory(name='u1', rank='mod')
    user2 = user_factory(name='u2', rank='mod')
    session.add_all([user1, user2])
    context = context_factory(request={'rank': 'admin'}, user=user1)
    with pytest.raises(errors.AuthError):
        user_detail_api.put(context, user1.name)
    with pytest.raises(errors.AuthError):
        user_detail_api.put(context, user2.name)

def test_uploading_avatar(
        tmpdir,
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api):
    config_injector({
        'data_dir': str(tmpdir.mkdir('data')),
        'data_url': 'http://example.com/data/',
        'thumbnails': {'avatar_width': 200, 'avatar_height': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {},
        'privileges': {'users:edit:self:avatar': 'mod'},
    })
    user = user_factory(name='u1', rank='mod')
    session.add(user)
    empty_pixel = \
        b'\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x01\x00\x00\x00\x00' \
        b'\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x01\x00\x2c\x00\x00\x00\x00' \
        b'\x01\x00\x01\x00\x00\x02\x02\x4c\x01\x00\x3b'
    response = user_detail_api.put(
        context_factory(
            request={'avatarStyle': 'manual'},
            files={'avatar': empty_pixel},
            user=user),
        'u1')
    user = session.query(db.User).filter_by(name='u1').one()
    assert user.avatar_style == user.AVATAR_MANUAL
    assert response['user']['avatarUrl'] == \
        'http://example.com/data/avatars/u1.jpg'
