import pytest
import os
from datetime import datetime
from szurubooru import api, config, db, errors
from szurubooru.func import util, posts

@pytest.fixture
def test_ctx(
        tmpdir, config_injector, context_factory, post_factory, user_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'privileges': {
            'posts:delete': db.User.RANK_REGULAR,
        },
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.post_factory = post_factory
    ret.api = api.PostDetailApi()
    return ret

def test_deleting(test_ctx):
    db.session.add(test_ctx.post_factory(id=1))
    db.session.commit()
    result = test_ctx.api.delete(
        test_ctx.context_factory(
            input={'version': 1},
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
        1)
    assert result == {}
    assert db.session.query(db.Post).count() == 0
    assert os.path.exists(os.path.join(config.config['data_dir'], 'tags.json'))

def test_trying_to_delete_non_existing(test_ctx):
    with pytest.raises(posts.PostNotFoundError):
        test_ctx.api.delete(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)), '999')

def test_trying_to_delete_without_privileges(test_ctx):
    db.session.add(test_ctx.post_factory(id=1))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.delete(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)),
            1)
    assert db.session.query(db.Post).count() == 1
