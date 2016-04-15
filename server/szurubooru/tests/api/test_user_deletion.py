import pytest
from datetime import datetime
from szurubooru import api, db, errors

@pytest.fixture
def user_detail_api():
    return api.UserDetailApi()

def test_removing_oneself(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api):
    config_injector({
        'privileges': {
            'users:delete:self': 'regular_user',
            'users:delete:any': 'mod',
        },
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
    })
    user1 = user_factory(name='u1', rank='regular_user')
    user2 = user_factory(name='u2', rank='regular_user')
    session.add_all([user1, user2])
    session.commit()
    with pytest.raises(errors.AuthError):
        user_detail_api.delete(context_factory(user=user1), 'u2')
    user_detail_api.delete(context_factory(user=user1), 'u1')
    assert [u.name for u in session.query(db.User).all()] == ['u2']

def test_removing_someone_else(
        session,
        config_injector,
        context_factory,
        user_factory,
        user_detail_api):
    config_injector({
        'privileges': {
            'users:delete:self': 'regular_user',
            'users:delete:any': 'mod',
        },
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
    })
    user1 = user_factory(name='u1', rank='regular_user')
    user2 = user_factory(name='u2', rank='regular_user')
    mod_user = user_factory(rank='mod')
    session.add_all([user1, user2])
    session.commit()
    user_detail_api.delete(context_factory(user=mod_user), 'u1')
    user_detail_api.delete(context_factory(user=mod_user), 'u2')
    assert session.query(db.User).all() == []

def test_removing_non_existing(
        context_factory, config_injector, user_factory, user_detail_api):
    config_injector({
        'privileges': {
            'users:delete:self': 'regular_user',
            'users:delete:any': 'mod',
        },
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
    })
    with pytest.raises(errors.NotFoundError):
        user_detail_api.delete(
            context_factory(user=user_factory(rank='regular_user')), 'bad')

