import pytest
import os
from datetime import datetime
from szurubooru import api, config, db, errors
from szurubooru.util import misc, tags

@pytest.fixture
def test_ctx(
        tmpdir,
        session,
        config_injector,
        context_factory,
        tag_factory,
        user_factory):
    config_injector({
        'data_dir': str(tmpdir),
        'privileges': {
            'tags:delete': 'regular_user',
        },
        'ranks': ['anonymous', 'regular_user'],
    })
    ret = misc.dotdict()
    ret.session = session
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.api = api.TagDetailApi()
    return ret

def test_removing_tags(test_ctx):
    test_ctx.session.add(test_ctx.tag_factory(names=['tag']))
    test_ctx.session.commit()
    result = test_ctx.api.delete(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank='regular_user')),
        'tag')
    assert result == {}
    assert test_ctx.session.query(db.Tag).count() == 0
    assert os.path.exists(os.path.join(config.config['data_dir'], 'tags.json'))

def test_removing_tags_without_privileges(test_ctx):
    test_ctx.session.add(test_ctx.tag_factory(names=['tag']))
    test_ctx.session.commit()
    with pytest.raises(errors.AuthError):
        test_ctx.api.delete(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank='anonymous')),
            'tag')
    assert test_ctx.session.query(db.Tag).count() == 1

def test_removing_tags_with_usages(test_ctx, post_factory):
    tag = test_ctx.tag_factory(names=['tag'])
    post = post_factory()
    post.tags.append(tag)
    test_ctx.session.add_all([tag, post])
    test_ctx.session.commit()
    with pytest.raises(tags.TagIsInUseError):
        test_ctx.api.delete(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank='regular_user')),
            'tag')
    assert test_ctx.session.query(db.Tag).count() == 1

def test_removing_non_existing(test_ctx):
    with pytest.raises(tags.TagNotFoundError):
        test_ctx.api.delete(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank='regular_user')), 'bad')
