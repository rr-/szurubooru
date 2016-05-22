import datetime
import pytest
from szurubooru import api, db, errors
from szurubooru.func import util, tags

def assert_results(result, expected_tag_names_and_occurrences):
    actual_tag_names_and_occurences = []
    for item in result['results']:
        tag_name = item['tag']['names'][0]
        occurrences = item['occurrences']
        actual_tag_names_and_occurences.append((tag_name, occurrences))
    assert actual_tag_names_and_occurences == expected_tag_names_and_occurrences

@pytest.fixture
def test_ctx(
        context_factory, config_injector, user_factory, tag_factory, post_factory):
    config_injector({
        'privileges': {
            'tags:view': db.User.RANK_REGULAR,
        },
        'thumbnails': {'avatar_width': 200},
    })
    ret = util.dotdict()
    ret.context_factory = context_factory
    ret.user_factory = user_factory
    ret.tag_factory = tag_factory
    ret.post_factory = post_factory
    ret.api = api.TagSiblingsApi()
    return ret

def test_unused(test_ctx):
    db.session.add(test_ctx.tag_factory(names=['tag']))
    result = test_ctx.api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)), 'tag')
    assert_results(result, [])

def test_used_alone(test_ctx):
    tag = test_ctx.tag_factory(names=['tag'])
    post = test_ctx.post_factory()
    post.tags = [tag]
    db.session.add_all([post, tag])
    result = test_ctx.api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)), 'tag')
    assert_results(result, [])

def test_used_with_others(test_ctx):
    tag1 = test_ctx.tag_factory(names=['tag1'])
    tag2 = test_ctx.tag_factory(names=['tag2'])
    post = test_ctx.post_factory()
    post.tags = [tag1, tag2]
    db.session.add_all([post, tag1, tag2])
    result = test_ctx.api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)), 'tag1')
    assert_results(result, [('tag2', 1)])
    result = test_ctx.api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)), 'tag2')
    assert_results(result, [('tag1', 1)])

def test_used_with_multiple_others(test_ctx):
    tag1 = test_ctx.tag_factory(names=['tag1'])
    tag2 = test_ctx.tag_factory(names=['tag2'])
    tag3 = test_ctx.tag_factory(names=['tag3'])
    post1 = test_ctx.post_factory()
    post2 = test_ctx.post_factory()
    post3 = test_ctx.post_factory()
    post4 = test_ctx.post_factory()
    post1.tags = [tag1, tag2, tag3]
    post2.tags = [tag1, tag3]
    post3.tags = [tag2]
    post4.tags = [tag2]
    db.session.add_all([post1, post2, post3, post4, tag1, tag2, tag3])
    result = test_ctx.api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)), 'tag1')
    assert_results(result, [('tag3', 2), ('tag2', 1)])
    result = test_ctx.api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)), 'tag2')
    assert_results(result, [('tag1', 1), ('tag3', 1)])
    result = test_ctx.api.get(
        test_ctx.context_factory(
            user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)), 'tag3')
    # even though tag2 is used more widely, tag1 is more relevant to tag3
    assert_results(result, [('tag1', 2), ('tag2', 1)])

def test_trying_to_retrieve_non_existing(test_ctx):
    with pytest.raises(tags.TagNotFoundError):
        test_ctx.api.get(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank=db.User.RANK_REGULAR)), '-')

def test_trying_to_retrieve_without_privileges(test_ctx):
    with pytest.raises(errors.AuthError):
        test_ctx.api.get(
            test_ctx.context_factory(
                user=test_ctx.user_factory(rank=db.User.RANK_ANONYMOUS)), '-')
