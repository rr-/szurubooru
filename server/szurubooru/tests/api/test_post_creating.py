import datetime
import os
import unittest.mock
import pytest
from szurubooru import api, db, errors
from szurubooru.func import posts, tags, snapshots, net

@pytest.fixture(autouse=True)
def inject_config(config_injector):
    config_injector({
        'privileges': {
            'posts:create:anonymous': db.User.RANK_REGULAR,
            'posts:create:identified': db.User.RANK_REGULAR,
            'tags:create': db.User.RANK_REGULAR,
        },
    })

def test_creating_minimal_posts(
        context_factory, post_factory, user_factory):
    auth_user = user_factory(rank=db.User.RANK_REGULAR)
    post = post_factory()
    db.session.add(post)
    db.session.flush()

    with unittest.mock.patch('szurubooru.func.posts.create_post'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_safety'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_source'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_relations'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_notes'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_flags'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_thumbnail'), \
            unittest.mock.patch('szurubooru.func.posts.serialize_post'), \
            unittest.mock.patch('szurubooru.func.tags.export_to_json'), \
            unittest.mock.patch('szurubooru.func.snapshots.save_entity_creation'):
        posts.create_post.return_value = (post, [])
        posts.serialize_post.return_value = 'serialized post'

        result = api.PostListApi().post(
            context_factory(
                input={
                    'safety': 'safe',
                    'tags': ['tag1', 'tag2'],
                },
                files={
                    'content': 'post-content',
                    'thumbnail': 'post-thumbnail',
                },
                user=auth_user))

        assert result == 'serialized post'
        posts.create_post.assert_called_once_with(
            'post-content', ['tag1', 'tag2'], auth_user)
        posts.update_post_thumbnail.assert_called_once_with(post, 'post-thumbnail')
        posts.update_post_safety.assert_called_once_with(post, 'safe')
        posts.update_post_source.assert_called_once_with(post, None)
        posts.update_post_relations.assert_called_once_with(post, [])
        posts.update_post_notes.assert_called_once_with(post, [])
        posts.update_post_flags.assert_called_once_with(post, [])
        posts.update_post_thumbnail.assert_called_once_with(post, 'post-thumbnail')
        posts.serialize_post.assert_called_once_with(post, auth_user, options=None)
        tags.export_to_json.assert_called_once_with()
        snapshots.save_entity_creation.assert_called_once_with(post, auth_user)

def test_creating_full_posts(context_factory, post_factory, user_factory):
    auth_user = user_factory(rank=db.User.RANK_REGULAR)
    post = post_factory()
    db.session.add(post)
    db.session.flush()

    with unittest.mock.patch('szurubooru.func.posts.create_post'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_safety'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_source'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_relations'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_notes'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_flags'), \
            unittest.mock.patch('szurubooru.func.posts.serialize_post'), \
            unittest.mock.patch('szurubooru.func.tags.export_to_json'), \
            unittest.mock.patch('szurubooru.func.snapshots.save_entity_creation'):
        posts.create_post.return_value = (post, [])
        posts.serialize_post.return_value = 'serialized post'

        result = api.PostListApi().post(
            context_factory(
                input={
                    'safety': 'safe',
                    'tags': ['tag1', 'tag2'],
                    'relations': [1, 2],
                    'source': 'source',
                    'notes': ['note1', 'note2'],
                    'flags': ['flag1', 'flag2'],
                },
                files={
                    'content': 'post-content',
                },
                user=auth_user))

        assert result == 'serialized post'
        posts.create_post.assert_called_once_with(
            'post-content', ['tag1', 'tag2'], auth_user)
        posts.update_post_safety.assert_called_once_with(post, 'safe')
        posts.update_post_source.assert_called_once_with(post, 'source')
        posts.update_post_relations.assert_called_once_with(post, [1, 2])
        posts.update_post_notes.assert_called_once_with(post, ['note1', 'note2'])
        posts.update_post_flags.assert_called_once_with(post, ['flag1', 'flag2'])
        posts.serialize_post.assert_called_once_with(post, auth_user, options=None)
        tags.export_to_json.assert_called_once_with()
        snapshots.save_entity_creation.assert_called_once_with(post, auth_user)

def test_anonymous_uploads(
        config_injector, context_factory, post_factory, user_factory):
    auth_user = user_factory(rank=db.User.RANK_REGULAR)
    post = post_factory()
    db.session.add(post)
    db.session.flush()

    with unittest.mock.patch('szurubooru.func.tags.export_to_json'), \
            unittest.mock.patch('szurubooru.func.snapshots.save_entity_creation'), \
            unittest.mock.patch('szurubooru.func.posts.serialize_post'), \
            unittest.mock.patch('szurubooru.func.posts.create_post'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_source'):
        config_injector({
            'privileges': {'posts:create:anonymous': db.User.RANK_REGULAR},
        })
        posts.create_post.return_value = [post, []]
        api.PostListApi().post(
            context_factory(
                input={
                    'safety': 'safe',
                    'tags': ['tag1', 'tag2'],
                    'anonymous': 'True',
                },
                files={
                    'content': 'post-content',
                },
                user=auth_user))
        posts.create_post.assert_called_once_with(
            'post-content', ['tag1', 'tag2'], None)

def test_creating_from_url_saves_source(
        config_injector, context_factory, post_factory, user_factory):
    auth_user = user_factory(rank=db.User.RANK_REGULAR)
    post = post_factory()
    db.session.add(post)
    db.session.flush()

    with unittest.mock.patch('szurubooru.func.net.download'), \
            unittest.mock.patch('szurubooru.func.tags.export_to_json'), \
            unittest.mock.patch('szurubooru.func.snapshots.save_entity_creation'), \
            unittest.mock.patch('szurubooru.func.posts.serialize_post'), \
            unittest.mock.patch('szurubooru.func.posts.create_post'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_source'):
        config_injector({
            'privileges': {'posts:create:identified': db.User.RANK_REGULAR},
        })
        net.download.return_value = b'content'
        posts.create_post.return_value = [post, []]
        api.PostListApi().post(
            context_factory(
                input={
                    'safety': 'safe',
                    'tags': ['tag1', 'tag2'],
                    'contentUrl': 'example.com',
                },
                user=auth_user))
        net.download.assert_called_once_with('example.com')
        posts.create_post.assert_called_once_with(
            b'content', ['tag1', 'tag2'], auth_user)
        posts.update_post_source.assert_called_once_with(post, 'example.com')

def test_creating_from_url_with_source_specified(
        config_injector, context_factory, post_factory, user_factory):
    auth_user = user_factory(rank=db.User.RANK_REGULAR)
    post = post_factory()
    db.session.add(post)
    db.session.flush()

    with unittest.mock.patch('szurubooru.func.net.download'), \
            unittest.mock.patch('szurubooru.func.tags.export_to_json'), \
            unittest.mock.patch('szurubooru.func.snapshots.save_entity_creation'), \
            unittest.mock.patch('szurubooru.func.posts.serialize_post'), \
            unittest.mock.patch('szurubooru.func.posts.create_post'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_source'):
        config_injector({
            'privileges': {'posts:create:identified': db.User.RANK_REGULAR},
        })
        net.download.return_value = b'content'
        posts.create_post.return_value = [post, []]
        api.PostListApi().post(
            context_factory(
                input={
                    'safety': 'safe',
                    'tags': ['tag1', 'tag2'],
                    'contentUrl': 'example.com',
                    'source': 'example2.com',
                },
                user=auth_user))
        net.download.assert_called_once_with('example.com')
        posts.create_post.assert_called_once_with(
            b'content', ['tag1', 'tag2'], auth_user)
        posts.update_post_source.assert_called_once_with(post, 'example2.com')

@pytest.mark.parametrize('field', ['tags', 'safety'])
def test_trying_to_omit_mandatory_field(context_factory, user_factory, field):
    input = {
        'safety': 'safe',
        'tags': ['tag1', 'tag2'],
    }
    del input[field]
    with pytest.raises(errors.MissingRequiredParameterError):
        api.PostListApi().post(
            context_factory(
                input=input,
                files={'content': '...'},
                user=user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_omit_content(context_factory, user_factory):
    with pytest.raises(errors.MissingRequiredFileError):
        api.PostListApi().post(
            context_factory(
                input={
                    'safety': 'safe',
                    'tags': ['tag1', 'tag2'],
                },
                user=user_factory(rank=db.User.RANK_REGULAR)))

def test_trying_to_create_post_without_privileges(context_factory, user_factory):
    with pytest.raises(errors.AuthError):
        api.PostListApi().post(
            context_factory(
                input='whatever',
                user=user_factory(rank=db.User.RANK_ANONYMOUS)))

def test_trying_to_create_tags_without_privileges(
        config_injector, context_factory, user_factory):
    config_injector({
        'privileges': {
            'posts:create:anonymous': db.User.RANK_REGULAR,
            'posts:create:identified': db.User.RANK_REGULAR,
            'tags:create': db.User.RANK_ADMINISTRATOR,
        },
    })
    with pytest.raises(errors.AuthError), \
            unittest.mock.patch('szurubooru.func.posts.update_post_content'), \
            unittest.mock.patch('szurubooru.func.posts.update_post_tags'):
        posts.update_post_tags.return_value = ['new-tag']
        api.PostListApi().post(
            context_factory(
                input={
                    'safety': 'safe',
                    'tags': ['tag1', 'tag2'],
                },
                files={
                    'content': posts.EMPTY_PIXEL,
                },
                user=user_factory(rank=db.User.RANK_REGULAR)))
