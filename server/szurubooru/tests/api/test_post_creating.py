from unittest.mock import patch
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

    with patch('szurubooru.func.posts.create_post'), \
            patch('szurubooru.func.posts.update_post_safety'), \
            patch('szurubooru.func.posts.update_post_source'), \
            patch('szurubooru.func.posts.update_post_relations'), \
            patch('szurubooru.func.posts.update_post_notes'), \
            patch('szurubooru.func.posts.update_post_flags'), \
            patch('szurubooru.func.posts.update_post_thumbnail'), \
            patch('szurubooru.func.posts.serialize_post'), \
            patch('szurubooru.func.tags.export_to_json'), \
            patch('szurubooru.func.snapshots.create'):
        posts.create_post.return_value = (post, [])
        posts.serialize_post.return_value = 'serialized post'

        result = api.post_api.create_post(
            context_factory(
                params={
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
        posts.update_post_thumbnail.assert_called_once_with(
            post, 'post-thumbnail')
        posts.update_post_safety.assert_called_once_with(post, 'safe')
        posts.update_post_source.assert_called_once_with(post, None)
        posts.update_post_relations.assert_called_once_with(post, [])
        posts.update_post_notes.assert_called_once_with(post, [])
        posts.update_post_flags.assert_called_once_with(post, [])
        posts.update_post_thumbnail.assert_called_once_with(
            post, 'post-thumbnail')
        posts.serialize_post.assert_called_once_with(
            post, auth_user, options=None)
        snapshots.create.assert_called_once_with(post, auth_user)
        tags.export_to_json.assert_called_once_with()


def test_creating_full_posts(context_factory, post_factory, user_factory):
    auth_user = user_factory(rank=db.User.RANK_REGULAR)
    post = post_factory()
    db.session.add(post)
    db.session.flush()

    with patch('szurubooru.func.posts.create_post'), \
            patch('szurubooru.func.posts.update_post_safety'), \
            patch('szurubooru.func.posts.update_post_source'), \
            patch('szurubooru.func.posts.update_post_relations'), \
            patch('szurubooru.func.posts.update_post_notes'), \
            patch('szurubooru.func.posts.update_post_flags'), \
            patch('szurubooru.func.posts.serialize_post'), \
            patch('szurubooru.func.tags.export_to_json'), \
            patch('szurubooru.func.snapshots.create'):
        posts.create_post.return_value = (post, [])
        posts.serialize_post.return_value = 'serialized post'

        result = api.post_api.create_post(
            context_factory(
                params={
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
        posts.update_post_notes.assert_called_once_with(
            post, ['note1', 'note2'])
        posts.update_post_flags.assert_called_once_with(
            post, ['flag1', 'flag2'])
        posts.serialize_post.assert_called_once_with(
            post, auth_user, options=None)
        snapshots.create.assert_called_once_with(post, auth_user)
        tags.export_to_json.assert_called_once_with()


def test_anonymous_uploads(
        config_injector, context_factory, post_factory, user_factory):
    auth_user = user_factory(rank=db.User.RANK_REGULAR)
    post = post_factory()
    db.session.add(post)
    db.session.flush()

    with patch('szurubooru.func.tags.export_to_json'), \
            patch('szurubooru.func.posts.serialize_post'), \
            patch('szurubooru.func.posts.create_post'), \
            patch('szurubooru.func.posts.update_post_source'):
        config_injector({
            'privileges': {'posts:create:anonymous': db.User.RANK_REGULAR},
        })
        posts.create_post.return_value = [post, []]
        api.post_api.create_post(
            context_factory(
                params={
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

    with patch('szurubooru.func.net.download'), \
            patch('szurubooru.func.tags.export_to_json'), \
            patch('szurubooru.func.posts.serialize_post'), \
            patch('szurubooru.func.posts.create_post'), \
            patch('szurubooru.func.posts.update_post_source'):
        config_injector({
            'privileges': {'posts:create:identified': db.User.RANK_REGULAR},
        })
        net.download.return_value = b'content'
        posts.create_post.return_value = [post, []]
        api.post_api.create_post(
            context_factory(
                params={
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

    with patch('szurubooru.func.net.download'), \
            patch('szurubooru.func.tags.export_to_json'), \
            patch('szurubooru.func.posts.serialize_post'), \
            patch('szurubooru.func.posts.create_post'), \
            patch('szurubooru.func.posts.update_post_source'):
        config_injector({
            'privileges': {'posts:create:identified': db.User.RANK_REGULAR},
        })
        net.download.return_value = b'content'
        posts.create_post.return_value = [post, []]
        api.post_api.create_post(
            context_factory(
                params={
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


@pytest.mark.parametrize('field', ['safety'])
def test_trying_to_omit_mandatory_field(context_factory, user_factory, field):
    params = {
        'safety': 'safe',
    }
    del params[field]
    with pytest.raises(errors.MissingRequiredParameterError):
        api.post_api.create_post(
            context_factory(
                params=params,
                files={'content': '...'},
                user=user_factory(rank=db.User.RANK_REGULAR)))


@pytest.mark.parametrize(
    'field', ['tags', 'relations', 'source', 'notes', 'flags'])
def test_omitting_optional_field(
        field, context_factory, post_factory, user_factory):
    auth_user = user_factory(rank=db.User.RANK_REGULAR)
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    params = {
        'safety': 'safe',
        'tags': ['tag1', 'tag2'],
        'relations': [1, 2],
        'source': 'source',
        'notes': ['note1', 'note2'],
        'flags': ['flag1', 'flag2'],
    }
    del params[field]
    with patch('szurubooru.func.posts.create_post'), \
            patch('szurubooru.func.posts.update_post_safety'), \
            patch('szurubooru.func.posts.update_post_source'), \
            patch('szurubooru.func.posts.update_post_relations'), \
            patch('szurubooru.func.posts.update_post_notes'), \
            patch('szurubooru.func.posts.update_post_flags'), \
            patch('szurubooru.func.posts.serialize_post'), \
            patch('szurubooru.func.tags.export_to_json'), \
            patch('szurubooru.func.snapshots.create'):
        posts.create_post.return_value = (post, [])
        posts.serialize_post.return_value = 'serialized post'
        result = api.post_api.create_post(
            context_factory(
                params=params,
                files={'content': 'post-content'},
                user=auth_user))
        assert result == 'serialized post'


def test_errors_not_spending_ids(
        config_injector, tmpdir, context_factory, read_asset, user_factory,
        skip_post_hashing):
    config_injector({
        'data_dir': str(tmpdir.mkdir('data')),
        'data_url': 'example.com',
        'thumbnails': {
            'post_width': 300,
            'post_height': 300,
        },
        'privileges': {
            'posts:create:identified': db.User.RANK_REGULAR,
        },
    })
    auth_user = user_factory(rank=db.User.RANK_REGULAR)

    # successful request
    with patch('szurubooru.func.posts.serialize_post'), \
            patch('szurubooru.func.posts.update_post_tags'):
        posts.serialize_post.side_effect = lambda post, *_, **__: post.post_id
        post1_id = api.post_api.create_post(
            context_factory(
                params={'safety': 'safe', 'tags': []},
                files={'content': read_asset('png.png')},
                user=auth_user))
    db.session.commit()

    # erroreous request (duplicate post)
    with pytest.raises(posts.PostAlreadyUploadedError):
        api.post_api.create_post(
            context_factory(
                params={'safety': 'safe', 'tags': []},
                files={'content': read_asset('png.png')},
                user=auth_user))
    db.session.rollback()

    # successful request
    with patch('szurubooru.func.posts.serialize_post'), \
            patch('szurubooru.func.posts.update_post_tags'):
        posts.serialize_post.side_effect = lambda post, *_, **__: post.post_id
        post2_id = api.post_api.create_post(
            context_factory(
                params={'safety': 'safe', 'tags': []},
                files={'content': read_asset('jpeg.jpg')},
                user=auth_user))

    assert post1_id > 0
    assert post2_id > 0
    assert post2_id == post1_id + 1


def test_trying_to_omit_content(context_factory, user_factory):
    with pytest.raises(errors.MissingRequiredFileError):
        api.post_api.create_post(
            context_factory(
                params={
                    'safety': 'safe',
                    'tags': ['tag1', 'tag2'],
                },
                user=user_factory(rank=db.User.RANK_REGULAR)))


def test_trying_to_create_post_without_privileges(
        context_factory, user_factory):
    with pytest.raises(errors.AuthError):
        api.post_api.create_post(context_factory(
            params='whatever',
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
            patch('szurubooru.func.posts.update_post_content'), \
            patch('szurubooru.func.posts.update_post_tags'):
        posts.update_post_tags.return_value = ['new-tag']
        api.post_api.create_post(
            context_factory(
                params={
                    'safety': 'safe',
                    'tags': ['tag1', 'tag2'],
                },
                files={
                    'content': posts.EMPTY_PIXEL,
                },
                user=user_factory(rank=db.User.RANK_REGULAR)))
