import os
import datetime
import unittest.mock
import pytest
from szurubooru import db
from szurubooru.func import posts, users, comments, snapshots, tags, images

@pytest.mark.parametrize('input_mime_type,expected_url', [
    ('image/jpeg', 'http://example.com/posts/1.jpg'),
    ('image/gif', 'http://example.com/posts/1.gif'),
    ('totally/unknown', 'http://example.com/posts/1.dat'),
])
def test_get_post_url(input_mime_type, expected_url, config_injector):
    config_injector({'data_url': 'http://example.com/'})
    post = db.Post()
    post.post_id = 1
    post.mime_type = input_mime_type
    assert posts.get_post_content_url(post) == expected_url

@pytest.mark.parametrize('input_mime_type', ['image/jpeg', 'image/gif'])
def test_get_post_thumbnail_url(input_mime_type, config_injector):
    config_injector({'data_url': 'http://example.com/'})
    post = db.Post()
    post.post_id = 1
    post.mime_type = input_mime_type
    assert posts.get_post_thumbnail_url(post) \
        == 'http://example.com/generated-thumbnails/1.jpg'

@pytest.mark.parametrize('input_mime_type,expected_path', [
    ('image/jpeg', 'posts/1.jpg'),
    ('image/gif', 'posts/1.gif'),
    ('totally/unknown', 'posts/1.dat'),
])
def test_get_post_content_path(input_mime_type, expected_path):
    post = db.Post()
    post.post_id = 1
    post.mime_type = input_mime_type
    assert posts.get_post_content_path(post) == expected_path

@pytest.mark.parametrize('input_mime_type', ['image/jpeg', 'image/gif'])
def test_get_post_thumbnail_path(input_mime_type):
    post = db.Post()
    post.post_id = 1
    post.mime_type = input_mime_type
    assert posts.get_post_thumbnail_path(post) == 'generated-thumbnails/1.jpg'

@pytest.mark.parametrize('input_mime_type', ['image/jpeg', 'image/gif'])
def test_get_post_thumbnail_backup_path(input_mime_type):
    post = db.Post()
    post.post_id = 1
    post.mime_type = input_mime_type
    assert posts.get_post_thumbnail_backup_path(post) \
        == 'posts/custom-thumbnails/1.dat'

def test_serialize_note():
    note = db.PostNote()
    note.polygon = [[0, 1], [1, 1], [1, 0], [0, 0]]
    note.text = '...'
    assert posts.serialize_note(note) == {
        'polygon': [[0, 1], [1, 1], [1, 0], [0, 0]],
        'text': '...'
    }

def test_serialize_empty_post():
    assert posts.serialize_post(None, None) is None

def test_serialize_post(
        post_factory, user_factory, comment_factory, tag_factory, config_injector):
    config_injector({'data_url': 'http://example.com/'})
    with unittest.mock.patch('szurubooru.func.comments.serialize_comment'), \
            unittest.mock.patch('szurubooru.func.users.serialize_micro_user'), \
            unittest.mock.patch('szurubooru.func.posts.files.has', return_value=True), \
            unittest.mock.patch('szurubooru.func.snapshots.get_serialized_history'):
        users.serialize_micro_user.side_effect = lambda user: user.name
        comments.serialize_comment.side_effect \
            = lambda comment, auth_user: comment.user.name
        snapshots.get_serialized_history.return_value = 'snapshot history'

        auth_user = user_factory(name='auth user')
        post = db.Post()
        post.post_id = 1
        post.creation_time = datetime.datetime(1997, 1, 1)
        post.last_edit_time = datetime.datetime(1998, 1, 1)
        post.tags = [
            tag_factory(names=['tag1', 'tag2']),
            tag_factory(names=['tag3'])
        ]
        post.safety = db.Post.SAFETY_SAFE
        post.source = '4gag'
        post.type = db.Post.TYPE_IMAGE
        post.checksum = 'deadbeef'
        post.mime_type = 'image/jpeg'
        post.file_size = 100
        post.user = user_factory(name='post author')
        post.canvas_width = 200
        post.canvas_height = 300
        post.flags = ['loop']
        db.session.add(post)

        db.session.flush()
        db.session.add_all([
            comment_factory(user=user_factory(name='commenter1'), post=post),
            comment_factory(user=user_factory(name='commenter2'), post=post),
            db.PostFavorite(
                post=post,
                user=user_factory(name='fav1'),
                time=datetime.datetime(1800, 1, 1)),
            db.PostFeature(
                post=post,
                user=user_factory(),
                time=datetime.datetime(1999, 1, 1)),
            db.PostScore(
                post=post,
                user=auth_user,
                score=-1,
                time=datetime.datetime(1800, 1, 1)),
            db.PostScore(
                post=post,
                user=user_factory(),
                score=1,
                time=datetime.datetime(1800, 1, 1)),
            db.PostScore(
                post=post,
                user=user_factory(),
                score=1,
                time=datetime.datetime(1800, 1, 1))])
        db.session.flush()

        result = posts.serialize_post(post, auth_user)

    assert result == {
        'id': 1,
        'creationTime': datetime.datetime(1997, 1, 1),
        'lastEditTime': datetime.datetime(1998, 1, 1),
        'safety': 'safe',
        'source': '4gag',
        'type': 'image',
        'checksum': 'deadbeef',
        'fileSize': 100,
        'canvasWidth': 200,
        'canvasHeight': 300,
        'contentUrl': 'http://example.com/posts/1.jpg',
        'thumbnailUrl': 'http://example.com/generated-thumbnails/1.jpg',
        'flags': ['loop'],
        'tags': ['tag1', 'tag3'],
        'relations': [],
        'notes': [],
        'user': 'post author',
        'score': 1,
        'ownFavorite': False,
        'ownScore': -1,
        'tagCount': 2,
        'favoriteCount': 1,
        'commentCount': 2,
        'noteCount': 0,
        'featureCount': 1,
        'relationCount': 0,
        'lastFeatureTime': datetime.datetime(1999, 1, 1),
        'favoritedBy': ['fav1'],
        'hasCustomThumbnail': True,
        'mimeType': 'image/jpeg',
        'snapshots': 'snapshot history',
        'comments': ['commenter1', 'commenter2'],
    }

def test_get_post_count(post_factory):
    previous_count = posts.get_post_count()
    db.session.add_all([post_factory(), post_factory()])
    new_count = posts.get_post_count()
    assert previous_count == 0
    assert new_count == 2

def test_try_get_post_by_id(post_factory):
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    assert posts.try_get_post_by_id(post.post_id) == post
    assert posts.try_get_post_by_id(post.post_id + 1) is None

def test_get_post_by_id(post_factory):
    post = post_factory()
    db.session.add(post)
    db.session.flush()
    assert posts.get_post_by_id(post.post_id) == post
    with pytest.raises(posts.PostNotFoundError):
        posts.get_post_by_id(post.post_id + 1)

def test_create_post(user_factory, fake_datetime):
    with unittest.mock.patch('szurubooru.func.posts.update_post_content'), \
        unittest.mock.patch('szurubooru.func.posts.update_post_tags'), \
        fake_datetime('1997-01-01'):
        auth_user = user_factory()
        post = posts.create_post('content', ['tag'], auth_user)
        assert post.creation_time == datetime.datetime(1997, 1, 1)
        assert post.last_edit_time is None
        posts.update_post_tags.assert_called_once_with(post, ['tag'])
        posts.update_post_content.assert_called_once_with(post, 'content')

@pytest.mark.parametrize('input_safety,expected_safety', [
    ('safe', db.Post.SAFETY_SAFE),
    ('sketchy', db.Post.SAFETY_SKETCHY),
    ('unsafe', db.Post.SAFETY_UNSAFE),
])
def test_update_post_safety(input_safety, expected_safety):
    post = db.Post()
    posts.update_post_safety(post, input_safety)
    assert post.safety == expected_safety

def test_update_post_invalid_safety():
    post = db.Post()
    with pytest.raises(posts.InvalidPostSafetyError):
        posts.update_post_safety(post, 'bad')

def test_update_post_source():
    post = db.Post()
    posts.update_post_source(post, 'x')
    assert post.source == 'x'

def test_update_post_invalid_source():
    post = db.Post()
    with pytest.raises(posts.InvalidPostSourceError):
        posts.update_post_source(post, 'x' * 1000)

@pytest.mark.parametrize(
    'input_file,expected_mime_type,expected_type,output_file_name', [
    ('png.png', 'image/png', db.Post.TYPE_IMAGE, '1.png'),
    ('jpeg.jpg', 'image/jpeg', db.Post.TYPE_IMAGE, '1.jpg'),
    ('gif.gif', 'image/gif', db.Post.TYPE_IMAGE, '1.gif'),
    ('gif-animated.gif', 'image/gif', db.Post.TYPE_ANIMATION, '1.gif'),
    ('webm.webm', 'video/webm', db.Post.TYPE_VIDEO, '1.webm'),
    ('mp4.mp4', 'video/mp4', db.Post.TYPE_VIDEO, '1.mp4'),
    ('flash.swf', 'application/x-shockwave-flash', db.Post.TYPE_FLASH, '1.swf'),
])
def test_update_post_content(
        tmpdir,
        config_injector,
        post_factory,
        read_asset,
        input_file,
        expected_mime_type,
        expected_type,
        output_file_name):
    with unittest.mock.patch('szurubooru.func.util.get_md5', return_value='crc'):
        config_injector({
            'data_dir': str(tmpdir.mkdir('data')),
            'thumbnails': {
                'post_width': 300,
                'post_height': 300,
            },
        })
        post = post_factory(id=1)
        db.session.add(post)
        db.session.flush()
        posts.update_post_content(post, read_asset(input_file))
    assert post.mime_type == expected_mime_type
    assert post.type == expected_type
    assert post.checksum == 'crc'
    assert os.path.exists(str(tmpdir) + '/data/posts/' + output_file_name)

def test_update_post_content_to_existing_content(
        tmpdir, config_injector, post_factory, read_asset):
    config_injector({
        'data_dir': str(tmpdir.mkdir('data')),
        'thumbnails': {
            'post_width': 300,
            'post_height': 300,
        },
    })
    post = post_factory()
    another_post = post_factory()
    db.session.add_all([post, another_post])
    db.session.flush()
    posts.update_post_content(post, read_asset('png.png'))
    with pytest.raises(posts.PostAlreadyUploadedError):
        posts.update_post_content(another_post, read_asset('png.png'))

def test_update_post_content_broken_content(
        tmpdir, config_injector, post_factory, read_asset):
    # the rationale behind this behavior is to salvage user upload even if the
    # server software thinks it's broken. chances are the server is wrong,
    # especially about flash movies.
    config_injector({
        'data_dir': str(tmpdir.mkdir('data')),
        'thumbnails': {
            'post_width': 300,
            'post_height': 300,
        },
    })
    post = post_factory()
    another_post = post_factory()
    db.session.add_all([post, another_post])
    db.session.flush()
    posts.update_post_content(post, read_asset('png-broken.png'))
    assert post.canvas_width is None
    assert post.canvas_height is None

@pytest.mark.parametrize('input_content', [None, b'not a media file'])
def test_update_post_invalid_content(input_content):
    post = db.Post()
    with pytest.raises(posts.InvalidPostContentError):
        posts.update_post_content(post, input_content)

def test_update_post_thumbnail_to_new_one(
        tmpdir, config_injector, read_asset, post_factory):
    config_injector({
        'data_dir': str(tmpdir.mkdir('data')),
        'thumbnails': {
            'post_width': 300,
            'post_height': 300,
        },
    })
    post = post_factory(id=1)
    db.session.add(post)
    db.session.flush()
    posts.update_post_content(post, read_asset('png.png'))
    posts.update_post_thumbnail(post, read_asset('jpeg.jpg'))
    assert os.path.exists(str(tmpdir) + '/data/posts/custom-thumbnails/1.dat')
    assert os.path.exists(str(tmpdir) + '/data/generated-thumbnails/1.jpg')
    with open(str(tmpdir) + '/data/posts/custom-thumbnails/1.dat', 'rb') as handle:
        assert handle.read() == read_asset('jpeg.jpg')

def test_update_post_thumbnail_to_default(
        tmpdir, config_injector, read_asset, post_factory):
    config_injector({
        'data_dir': str(tmpdir.mkdir('data')),
        'thumbnails': {
            'post_width': 300,
            'post_height': 300,
        },
    })
    post = post_factory(id=1)
    db.session.add(post)
    db.session.flush()
    posts.update_post_content(post, read_asset('png.png'))
    posts.update_post_thumbnail(post, read_asset('jpeg.jpg'))
    posts.update_post_thumbnail(post, None)
    assert not os.path.exists(str(tmpdir) + '/data/posts/custom-thumbnails/1.dat')
    assert os.path.exists(str(tmpdir) + '/data/generated-thumbnails/1.jpg')

def test_update_post_thumbnail_broken_thumbnail(
        tmpdir, config_injector, read_asset, post_factory):
    config_injector({
        'data_dir': str(tmpdir.mkdir('data')),
        'thumbnails': {
            'post_width': 300,
            'post_height': 300,
        },
    })
    post = post_factory(id=1)
    db.session.add(post)
    db.session.flush()
    posts.update_post_content(post, read_asset('png.png'))
    posts.update_post_thumbnail(post, read_asset('png-broken.png'))
    assert os.path.exists(str(tmpdir) + '/data/posts/custom-thumbnails/1.dat')
    assert os.path.exists(str(tmpdir) + '/data/generated-thumbnails/1.jpg')
    with open(str(tmpdir) + '/data/posts/custom-thumbnails/1.dat', 'rb') as handle:
        assert handle.read() == read_asset('png-broken.png')
    with open(str(tmpdir) + '/data/generated-thumbnails/1.jpg', 'rb') as handle:
        image = images.Image(handle.read())
        assert image.width == 1
        assert image.height == 1

def test_update_post_content_leaves_custom_thumbnail(
        tmpdir, config_injector, read_asset, post_factory):
    config_injector({
        'data_dir': str(tmpdir.mkdir('data')),
        'thumbnails': {
            'post_width': 300,
            'post_height': 300,
        },
    })
    post = post_factory(id=1)
    db.session.add(post)
    db.session.flush()
    posts.update_post_content(post, read_asset('png.png'))
    posts.update_post_thumbnail(post, read_asset('jpeg.jpg'))
    posts.update_post_content(post, read_asset('png.png'))
    assert os.path.exists(str(tmpdir) + '/data/posts/custom-thumbnails/1.dat')
    assert os.path.exists(str(tmpdir) + '/data/generated-thumbnails/1.jpg')

def test_update_post_tags(tag_factory):
    post = db.Post()
    with unittest.mock.patch('szurubooru.func.tags.get_or_create_tags_by_names'):
        tags.get_or_create_tags_by_names.side_effect \
            = lambda tag_names: \
                ([tag_factory(names=[name]) for name in tag_names], [])
        posts.update_post_tags(post, ['tag1', 'tag2'])
    assert len(post.tags) == 2
    assert post.tags[0].names[0].name == 'tag1'
    assert post.tags[1].names[0].name == 'tag2'

def test_update_post_relations(post_factory):
    relation1 = post_factory()
    relation2 = post_factory()
    db.session.add_all([relation1, relation2])
    db.session.flush()
    post = post_factory()
    posts.update_post_relations(post, [relation1.post_id, relation2.post_id])
    assert len(post.relations) == 2
    assert post.relations[0].post_id == relation1.post_id
    assert post.relations[1].post_id == relation2.post_id

def test_relation_bidirectionality(post_factory):
    relation1 = post_factory()
    relation2 = post_factory()
    db.session.add_all([relation1, relation2])
    db.session.flush()
    post = post_factory()
    posts.update_post_relations(post, [relation1.post_id, relation2.post_id])
    posts.update_post_relations(relation1, [])
    assert len(post.relations) == 1
    assert post.relations[0].post_id == relation2.post_id

def test_update_post_non_existing_relations():
    post = db.Post()
    with pytest.raises(posts.InvalidPostRelationError):
        posts.update_post_relations(post, [100])

def test_update_post_notes():
    post = db.Post()
    posts.update_post_notes(
        post,
        [
            {'polygon': [[0, 0], [0, 1], [1, 0], [0, 0]], 'text': 'text1'},
            {'polygon': [[0, 0], [0, 1], [1, 0], [0, 0]], 'text': 'text2'},
        ])
    assert len(post.notes) == 2
    assert post.notes[0].polygon == [[0, 0], [0, 1], [1, 0], [0, 0]]
    assert post.notes[0].text == 'text1'
    assert post.notes[1].polygon == [[0, 0], [0, 1], [1, 0], [0, 0]]
    assert post.notes[1].text == 'text2'

@pytest.mark.parametrize('input', [
    [{'polygon': [[0, 0]], 'text': '...'}],
    [{'polygon': [[0, 0], [0, 0], [0, 2]], 'text': '...'}],
    [{'polygon': [[0, 0], [0, 0], [0, '...']], 'text': '...'}],
    [{'polygon': [[0, 0], [0, 0], [0, 0, 0]], 'text': '...'}],
    [{'polygon': [[0, 0], [0, 0], [0]], 'text': '...'}],
    [{'polygon': [[0, 0], [0, 0], [0, 1]], 'text': ''}],
    [{'polygon': [[0, 0], [0, 0], [0, 1]], 'text': None}],
    [{'text': '...'}],
    [{'polygon': [[0, 0], [0, 0], [0, 1]]}],
])
def test_update_post_invalid_notes(input):
    post = db.Post()
    with pytest.raises(posts.InvalidPostNoteError):
        posts.update_post_notes(post, input)

def test_update_post_flags():
    post = db.Post()
    posts.update_post_flags(post, ['loop'])
    assert post.flags == ['loop']

def test_update_post_invalid_flags():
    post = db.Post()
    with pytest.raises(posts.InvalidPostFlagError):
        posts.update_post_flags(post, ['invalid'])

def test_featuring_post(post_factory, user_factory):
    post = post_factory()
    user = user_factory()

    previous_featured_post = posts.try_get_featured_post()
    posts.feature_post(post, user)
    new_featured_post = posts.try_get_featured_post()

    assert previous_featured_post is None
    assert new_featured_post == post
