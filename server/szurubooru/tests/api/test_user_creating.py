import pytest
from datetime import datetime
from szurubooru import api, db, errors
from szurubooru.util import auth

@pytest.fixture
def user_list_api():
    return api.UserListApi()

def test_creating_users(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_list_api):
    config_injector({
        'secret': '',
        'user_name_regex': '.{3,}',
        'password_regex': '.{3,}',
        'default_rank': 'regular_user',
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {},
        'privileges': {'users:create': 'anonymous'},
    })

    user_list_api.post(
        context_factory(
            input={
                'name': 'chewie1',
                'email': 'asd@asd.asd',
                'password': 'oks',
            },
            user=user_factory(rank='regular_user')))
    user_list_api.post(
        context_factory(
            input={
                'name': 'chewie2',
                'email': 'asd@asd.asd',
                'password': 'sok',
            },
            user=user_factory(rank='regular_user')))

    first_user = session.query(db.User).filter_by(name='chewie1').one()
    other_user = session.query(db.User).filter_by(name='chewie2').one()
    assert first_user.name == 'chewie1'
    assert first_user.email == 'asd@asd.asd'
    assert first_user.rank == 'admin'
    assert auth.is_valid_password(first_user, 'oks') is True
    assert auth.is_valid_password(first_user, 'invalid') is False
    assert other_user.name == 'chewie2'
    assert other_user.email == 'asd@asd.asd'
    assert other_user.rank == 'regular_user'
    assert auth.is_valid_password(other_user, 'sok') is True
    assert auth.is_valid_password(other_user, 'invalid') is False

def test_creating_user_that_already_exists(
        config_injector, context_factory, user_factory, user_list_api):
    config_injector({
        'secret': '',
        'user_name_regex': '.{3,}',
        'password_regex': '.{3,}',
        'default_rank': 'regular_user',
        'thumbnails': {'avatar_width': 200},
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'rank_names': {},
        'privileges': {'users:create': 'anonymous'},
    })
    user_list_api.post(
        context_factory(
            input={
                'name': 'chewie',
                'email': 'asd@asd.asd',
                'password': 'oks',
            },
            user=user_factory(rank='regular_user')))
    with pytest.raises(errors.IntegrityError):
        user_list_api.post(
            context_factory(
                input={
                    'name': 'chewie',
                    'email': 'asd@asd.asd',
                    'password': 'oks',
                },
                user=user_factory(rank='regular_user')))
    with pytest.raises(errors.IntegrityError):
        user_list_api.post(
            context_factory(
                input={
                    'name': 'CHEWIE',
                    'email': 'asd@asd.asd',
                    'password': 'oks',
                },
                user=user_factory(rank='regular_user')))

@pytest.mark.parametrize('field', ['name', 'email', 'password'])
def test_missing_field(
        config_injector, context_factory, user_factory, user_list_api, field):
    config_injector({
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
        'privileges': {'users:create': 'anonymous'},
    })
    request = {
        'name': 'chewie',
        'email': 'asd@asd.asd',
        'password': 'oks',
    }
    del request[field]
    with pytest.raises(errors.ValidationError):
        user_list_api.post(
            context_factory(
                input=request, user=user_factory(rank='regular_user')))
