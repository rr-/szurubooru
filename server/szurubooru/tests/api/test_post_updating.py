import datetime
import os
import unittest.mock
import pytest
from szurubooru import api, db, errors
from szurubooru.func import posts, tags, snapshots, net

def test_post_updating(
        config_injector, context_factory, post_factory, user_factory, fake_datetime):
    config_injector({
        'privileges': {
            'posts:edit:tags': db.User.RANK_REGULAR,
            'posts:edit:content': db.User.RANK_REGULAR,
            'posts:edit:safety': db.User.RANK_REGULAR,
            'posts:edit:source': db.User.RANK_REGULAR,
            'posts:edit:relations': db.User.RANK_REGULAR,
            'posts:edit:notes': db.User.RANK_REGULAR,
            'posts:edit:flags': db.User.RANK_REGULAR,
            'posts:edit:thumbnail': db.User.RANK_REGULAR,
        },
    })
    auth_user = user_factory(rank=db.User.RANK_REGULAR)
    post = post_factory()
    db.session.add(post)
    db.session.flush()

    with unittest.mock.patch('szurubooru.func.posts.create_post'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_tags'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_content'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_thumbnail'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_safety'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_source'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_relations'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_notes'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_flags'), \
        unittest.mock.patch('szurubooru.func.posts.serialize_post'), \
        unittest.mock.patch('szurubooru.func.tags.export_to_json'), \
        unittest.mock.patch('szurubooru.func.snapshots.save_entity_modification'):

        posts.serialize_post.return_value = 'serialized post'

        with fake_datetime('1997-01-01'):
            result = api.PostDetailApi().put(
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
                        'thumbnail': 'post-thumbnail',
                    },
                    user=auth_user),
                post.post_id)

        assert result == 'serialized post'
        posts.create_post.assert_not_called()
        posts.update_post_tags.assert_called_once_with(post, ['tag1', 'tag2'])
        posts.update_post_content.assert_called_once_with(post, 'post-content')
        posts.update_post_thumbnail.assert_called_once_with(post, 'post-thumbnail')
        posts.update_post_safety.assert_called_once_with(post, 'safe')
        posts.update_post_source.assert_called_once_with(post, 'source')
        posts.update_post_relations.assert_called_once_with(post, [1, 2])
        posts.update_post_notes.assert_called_once_with(post, ['note1', 'note2'])
        posts.update_post_flags.assert_called_once_with(post, ['flag1', 'flag2'])
        posts.serialize_post.assert_called_once_with(post, auth_user, options=None)
        tags.export_to_json.assert_called_once_with()
        snapshots.save_entity_modification.assert_called_once_with(post, auth_user)
        assert post.last_edit_time == datetime.datetime(1997, 1, 1)

def test_uploading_from_url_saves_source(
        config_injector, context_factory, post_factory, user_factory):
    config_injector({
        'privileges': {'posts:edit:content': db.User.RANK_REGULAR},
    })
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    with unittest.mock.patch('szurubooru.func.net.download'), \
        unittest.mock.patch('szurubooru.func.tags.export_to_json'), \
        unittest.mock.patch('szurubooru.func.snapshots.save_entity_modification'), \
        unittest.mock.patch('szurubooru.func.posts.serialize_post'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_content'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_source'):
        net.download.return_value = b'content'
        api.PostDetailApi().put(
            context_factory(
                input={'contentUrl': 'example.com'},
                user=user_factory(rank=db.User.RANK_REGULAR)),
            post.post_id)
        net.download.assert_called_once_with('example.com')
        posts.update_post_content.assert_called_once_with(post, b'content')
        posts.update_post_source.assert_called_once_with(post, 'example.com')

def test_uploading_from_url_with_source_specified(
        config_injector, context_factory, post_factory, user_factory):
    config_injector({
        'privileges': {
            'posts:edit:content': db.User.RANK_REGULAR,
            'posts:edit:source': db.User.RANK_REGULAR,
        },
    })
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    with unittest.mock.patch('szurubooru.func.net.download'), \
        unittest.mock.patch('szurubooru.func.tags.export_to_json'), \
        unittest.mock.patch('szurubooru.func.snapshots.save_entity_modification'), \
        unittest.mock.patch('szurubooru.func.posts.serialize_post'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_content'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_source'):
        net.download.return_value = b'content'
        api.PostDetailApi().put(
            context_factory(
                input={'contentUrl': 'example.com', 'source': 'example2.com'},
                user=user_factory(rank=db.User.RANK_REGULAR)),
            post.post_id)
        net.download.assert_called_once_with('example.com')
        posts.update_post_content.assert_called_once_with(post, b'content')
        posts.update_post_source.assert_called_once_with(post, 'example2.com')

def test_trying_to_update_non_existing(context_factory, user_factory):
    with pytest.raises(posts.PostNotFoundError):
        api.PostDetailApi().put(
            context_factory(
                input='whatever',
                user=user_factory(rank=db.User.RANK_REGULAR)),
            1)

@pytest.mark.parametrize('privilege,files,input', [
    ('posts:edit:tags', {}, {'tags': '...'}),
    ('posts:edit:safety', {}, {'safety': '...'}),
    ('posts:edit:source', {}, {'source': '...'}),
    ('posts:edit:relations', {}, {'relations': '...'}),
    ('posts:edit:notes', {}, {'notes': '...'}),
    ('posts:edit:flags', {}, {'flags': '...'}),
    ('posts:edit:content', {'content': '...'}, {}),
    ('posts:edit:thumbnail', {'thumbnail': '...'}, {}),
])
def test_trying_to_create_without_privileges(
        config_injector,
        context_factory,
        post_factory,
        user_factory,
        files,
        input,
        privilege):
    config_injector({
        'privileges': {privilege: db.User.RANK_REGULAR},
    })
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    with pytest.raises(errors.AuthError):
        api.PostDetailApi().put(
            context_factory(
                input=input,
                files=files,
                user=user_factory(rank=db.User.RANK_ANONYMOUS)),
            post.post_id)
