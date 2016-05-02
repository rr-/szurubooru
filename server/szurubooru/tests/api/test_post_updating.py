import datetime
import os
import unittest.mock
import pytest
from szurubooru import api, db, errors
from szurubooru.func import posts, tags, snapshots

def test_post_updating(
        config_injector, context_factory, post_factory, user_factory, fake_datetime):
    config_injector({
        'ranks': ['anonymous', 'regular_user'],
        'privileges': {
            'posts:edit:tags': 'regular_user',
            'posts:edit:content': 'regular_user',
            'posts:edit:safety': 'regular_user',
            'posts:edit:source': 'regular_user',
            'posts:edit:relations': 'regular_user',
            'posts:edit:notes': 'regular_user',
            'posts:edit:flags': 'regular_user',
            'posts:edit:thumbnail': 'regular_user',
        },
    })
    auth_user = user_factory(rank='regular_user')
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
        unittest.mock.patch('szurubooru.func.posts.serialize_post_with_details'), \
        unittest.mock.patch('szurubooru.func.tags.export_to_json'), \
        unittest.mock.patch('szurubooru.func.snapshots.save_entity_modification'):

        posts.serialize_post_with_details.return_value = 'serialized post'

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
        posts.serialize_post_with_details.assert_called_once_with(post, auth_user)
        tags.export_to_json.assert_called_once_with()
        snapshots.save_entity_modification.assert_called_once_with(post, auth_user)
        assert post.last_edit_time == datetime.datetime(1997, 1, 1)

def test_trying_to_update_non_existing(context_factory, user_factory):
    with pytest.raises(posts.PostNotFoundError):
        api.PostDetailApi().put(
            context_factory(
                input='whatever',
                user=user_factory(rank='regular_user')),
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
        'ranks': ['anonymous', 'regular_user'],
        'privileges': {privilege: 'regular_user'},
    })
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    with pytest.raises(errors.AuthError):
        api.PostDetailApi().put(
            context_factory(
                input=input,
                files=files,
                user=user_factory(rank='anonymous')),
            post.post_id)
