import pytest
from datetime import datetime
from szurubooru import api, db, errors
from szurubooru.func import util, users

@pytest.fixture
def test_ctx(config_injector, context_factory, user_factory):
    config_injector({
        'privileges': {
            'users:delete:self': db.User.RANK_REGULAR,
            'users:delete:any': db.User.RANK_MODERATOR,
        },
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.api = api.UserDetailApi()
    return ret

def test_deleting_oneself(test_ctx):
    user = test_ctx.user_factory(name='u', rank=db.User.RANK_REGULAR)
    db.session.add(user)
    db.session.commit()
    result = test_ctx.api.delete(
        test_ctx.context_factory(input={'version': 1}, user=user), 'u')
    assert result == {}
    assert db.session.query(db.User).count() == 0

def test_deleting_someone_else(test_ctx):
    user1 = test_ctx.user_factory(name='u1', rank=db.User.RANK_REGULAR)
    user2 = test_ctx.user_factory(name='u2', rank=db.User.RANK_MODERATOR)
    db.session.add_all([user1, user2])
    db.session.commit()
    test_ctx.api.delete(
        test_ctx.context_factory(input={'version': 1}, user=user2), 'u1')
    assert db.session.query(db.User).count() == 1

def test_trying_to_delete_someone_else_without_privileges(test_ctx):
    user1 = test_ctx.user_factory(name='u1', rank=db.User.RANK_REGULAR)
    user2 = test_ctx.user_factory(name='u2', rank=db.User.RANK_REGULAR)
    db.session.add_all([user1, user2])
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.delete(
            test_ctx.context_factory(input={'version': 1}, user=user2), 'u1')
    assert db.session.query(db.User).count() == 2

def test_trying_to_delete_non_existing(test_ctx):
    with pytest.raises(users.UserNotFoundError):
        test_ctx.api.delete(
            test_ctx.context_factory(
                input={'version': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
            'bad')
