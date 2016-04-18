import pytest
from datetime import datetime
from szurubooru import api, db, errors
from szurubooru.util import misc, users

@pytest.fixture
def test_ctx(session, config_injector, context_factory, user_factory):
    config_injector({
        'privileges': {
            'users:delete:self': 'regular_user',
            'users:delete:any': 'mod',
        },
        'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
    })
    ret = misc.dotdict()
    ret.session = session
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.api = api.UserDetailApi()
    return ret

def test_deleting_oneself(test_ctx):
    user1 = test_ctx.user_factory(name='u1', rank='regular_user')
    user2 = test_ctx.user_factory(name='u2', rank='regular_user')
    test_ctx.session.add_all([user1, user2])
    test_ctx.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.delete(test_ctx.context_factory(user=user1), 'u2')
    result = test_ctx.api.delete(test_ctx.context_factory(user=user1), 'u1')
    assert result == {}
    assert [u.name for u in test_ctx.session.query(db.User).all()] == ['u2']

def test_deleting_someone_else(test_ctx):
    user1 = test_ctx.user_factory(name='u1', rank='regular_user')
    user2 = test_ctx.user_factory(name='u2', rank='regular_user')
    mod_user = test_ctx.user_factory(rank='mod')
    test_ctx.session.add_all([user1, user2])
    test_ctx.session.commit()
    test_ctx.api.delete(test_ctx.context_factory(user=mod_user), 'u1')
    test_ctx.api.delete(test_ctx.context_factory(user=mod_user), 'u2')
    assert test_ctx.session.query(db.User).all() == []

def test_deleting_non_existing(test_ctx):
    with pytest.raises(users.UserNotFoundError):
        test_ctx.api.delete(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank='regular_user')), 'bad')

