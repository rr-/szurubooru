import pytest
import os
from datetime import datetime
from szurubooru import api, config, db, errors
from szurubooru.func import util, tags

@pytest.fixture
def test_ctx(
        tmpdir, config_injector, context_factory, tag_factory, user_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'privileges': {
            'tags:delete': db.User.RANK_REGULAR,
        },
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.api = api.TagDetailApi()
    return ret

def test_deleting(test_ctx):
    db.session.add(test_ctx.tag_factory(names=['tag']))
    db.session.commit()
    result = test_ctx.api.delete(
        test_ctx.context_factory(
            input={'version': 1},
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)),
        'tag')
    assert result == {}
    assert db.session.query(db.Tag).count() == 0
    assert os.path.exists(os.path.join(config.config['data_dir'], 'tags.json'))

def test_trying_to_delete_non_existing(test_ctx):
    with pytest.raises(tags.TagNotFoundError):
        test_ctx.api.delete(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)), 'bad')

def test_trying_to_delete_without_privileges(test_ctx):
    db.session.add(test_ctx.tag_factory(names=['tag']))
    db.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.delete(
            test_ctx.context_factory(
                input={'version': 1},
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)),
            'tag')
    assert db.session.query(db.Tag).count() == 1
