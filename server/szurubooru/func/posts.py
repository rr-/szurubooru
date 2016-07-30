import datetime
import sqlalchemy
from szurubooru import config, db, errors
from szurubooru.func import (
    users, snapshots, scores, comments, tags, tag_categories, util, mime, images, files)

EMPTY_PIXEL = \
    b'\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x01\x00\x00\x00\x00' \
    b'\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x01\x00\x2c\x00\x00\x00\x00' \
    b'\x01\x00\x01\x00\x00\x02\x02\x4c\x01\x00\x3b'

class PostNotFoundError(errors.NotFoundError): pass
class PostAlreadyFeaturedError(errors.ValidationError): pass
class PostAlreadyUploadedError(errors.ValidationError): pass
class InvalidPostIdError(errors.ValidationError): pass
class InvalidPostSafetyError(errors.ValidationError): pass
class InvalidPostSourceError(errors.ValidationError): pass
class InvalidPostContentError(errors.ValidationError): pass
class InvalidPostRelationError(errors.ValidationError): pass
class InvalidPostNoteError(errors.ValidationError): pass
class InvalidPostFlagError(errors.ValidationError): pass

SAFETY_MAP = {
    db.Post.SAFETY_SAFE: 'safe',
    db.Post.SAFETY_SKETCHY: 'sketchy',
    db.Post.SAFETY_UNSAFE: 'unsafe',
}
TYPE_MAP = {
    db.Post.TYPE_IMAGE: 'image',
    db.Post.TYPE_ANIMATION: 'animation',
    db.Post.TYPE_VIDEO: 'video',
    db.Post.TYPE_FLASH: 'flash',
}
FLAG_MAP = {
    db.Post.FLAG_LOOP: 'loop',
}

def get_post_content_url(post):
    return '%s/posts/%d.%s' % (
        config.config['data_url'].rstrip('/'),
        post.post_id,
        mime.get_extension(post.mime_type) or 'dat')

def get_post_thumbnail_url(post):
    return '%s/generated-thumbnails/%d.jpg' % (
        config.config['data_url'].rstrip('/'),
        post.post_id)

def get_post_content_path(post):
    return 'posts/%d.%s' % (
        post.post_id, mime.get_extension(post.mime_type) or 'dat')

def get_post_thumbnail_path(post):
    return 'generated-thumbnails/%d.jpg' % (post.post_id)

def get_post_thumbnail_backup_path(post):
    return 'posts/custom-thumbnails/%d.dat' % (post.post_id)

def serialize_note(note):
    return {
        'polygon': note.polygon,
        'text': note.text,
    }

def serialize_post(post, authenticated_user, options=None):
    return util.serialize_entity(
        post,
        {
            'id': lambda: post.post_id,
            'creationTime': lambda: post.creation_time,
            'lastEditTime': lambda: post.last_edit_time,
            'safety': lambda: SAFETY_MAP[post.safety],
            'source': lambda: post.source,
            'type': lambda: TYPE_MAP[post.type],
            'mimeType': lambda: post.mime_type,
            'checksum': lambda: post.checksum,
            'fileSize': lambda: post.file_size,
            'canvasWidth': lambda: post.canvas_width,
            'canvasHeight': lambda: post.canvas_height,
            'contentUrl': lambda: get_post_content_url(post),
            'thumbnailUrl': lambda: get_post_thumbnail_url(post),
            'flags': lambda: post.flags,
            'tags': lambda: [
                tag.names[0].name for tag in tags.sort_tags(post.tags)],
            'relations': lambda: sorted(
                {
                    post['id']:
                        post for post in [
                            serialize_micro_post(rel) for rel in post.relations
                        ]
                }.values(),
                key=lambda post: post['id']),
            'user': lambda: users.serialize_micro_user(post.user),
            'score': lambda: post.score,
            'ownScore': lambda: scores.get_score(post, authenticated_user),
            'ownFavorite': lambda: len(
                [user for user in post.favorited_by \
                    if user.user_id == authenticated_user.user_id]) > 0,
            'tagCount': lambda: post.tag_count,
            'favoriteCount': lambda: post.favorite_count,
            'commentCount': lambda: post.comment_count,
            'noteCount': lambda: post.note_count,
            'relationCount': lambda: post.relation_count,
            'featureCount': lambda: post.feature_count,
            'lastFeatureTime': lambda: post.last_feature_time,
            'favoritedBy': lambda: [
                users.serialize_micro_user(rel.user) \
                    for rel in post.favorited_by],
            'hasCustomThumbnail':
                lambda: files.has(get_post_thumbnail_backup_path(post)),
            'notes': lambda: sorted(
                [serialize_note(note) for note in post.notes],
                key=lambda x: x['polygon']),
            'comments': lambda: [
                comments.serialize_comment(comment, authenticated_user) \
                    for comment in sorted(
                        post.comments,
                        key=lambda comment: comment.creation_time)],
            'snapshots': lambda: snapshots.get_serialized_history(post),
        },
        options)

def serialize_micro_post(post):
    return serialize_post(
        post,
        authenticated_user=None,
        options=['id', 'thumbnailUrl'])

def get_post_count():
    return db.session.query(sqlalchemy.func.count(db.Post.post_id)).one()[0]

def try_get_post_by_id(post_id):
    try:
        post_id = int(post_id)
    except ValueError:
        raise InvalidPostIdError('Invalid post ID: %r.' % post_id)
    return db.session \
        .query(db.Post) \
        .filter(db.Post.post_id == post_id) \
        .one_or_none()

def get_post_by_id(post_id):
    post = try_get_post_by_id(post_id)
    if not post:
        raise PostNotFoundError('Post %r not found.' % post_id)
    return post

def try_get_current_post_feature():
    return db.session \
        .query(db.PostFeature) \
        .order_by(db.PostFeature.time.desc()) \
        .first()

def try_get_featured_post():
    post_feature = try_get_current_post_feature()
    return post_feature.post if post_feature else None

def create_post(content, tag_names, user):
    post = db.Post()
    post.safety = db.Post.SAFETY_SAFE
    post.user = user
    post.creation_time = datetime.datetime.utcnow()
    post.flags = []

    # we'll need post ID
    post.type = ''
    post.checksum = ''
    post.mime_type = ''
    db.session.add(post)
    db.session.flush()

    update_post_content(post, content)
    update_post_tags(post, tag_names)
    return post

def update_post_safety(post, safety):
    safety = util.flip(SAFETY_MAP).get(safety, None)
    if not safety:
        raise InvalidPostSafetyError(
            'Safety can be either of %r.' % list(SAFETY_MAP.values()))
    post.safety = safety

def update_post_source(post, source):
    if util.value_exceeds_column_size(source, db.Post.source):
        raise InvalidPostSourceError('Source is too long.')
    post.source = source

def update_post_content(post, content):
    if not content:
        raise InvalidPostContentError('Post content missing.')
    post.mime_type = mime.get_mime_type(content)
    if mime.is_flash(post.mime_type):
        post.type = db.Post.TYPE_FLASH
    elif mime.is_image(post.mime_type):
        if mime.is_animated_gif(content):
            post.type = db.Post.TYPE_ANIMATION
        else:
            post.type = db.Post.TYPE_IMAGE
    elif mime.is_video(post.mime_type):
        post.type = db.Post.TYPE_VIDEO
    else:
        raise InvalidPostContentError('Unhandled file type: %r' % post.mime_type)

    post.checksum = util.get_md5(content)
    other_post = db.session \
        .query(db.Post) \
        .filter(db.Post.checksum == post.checksum) \
        .filter(db.Post.post_id != post.post_id) \
        .one_or_none()
    if other_post:
        raise PostAlreadyUploadedError(
            'Post already uploaded (%d)' % other_post.post_id)

    post.file_size = len(content)
    try:
        image = images.Image(content)
        post.canvas_width = image.width
        post.canvas_height = image.height
    except errors.ProcessingError:
        post.canvas_width = None
        post.canvas_height = None
    files.save(get_post_content_path(post), content)
    update_post_thumbnail(post, content=None, delete=False)

def update_post_thumbnail(post, content=None, delete=True):
    if content is None:
        content = files.get(get_post_content_path(post))
        if delete:
            files.delete(get_post_thumbnail_backup_path(post))
    else:
        files.save(get_post_thumbnail_backup_path(post), content)
    generate_post_thumbnail(post)

def generate_post_thumbnail(post):
    if files.has(get_post_thumbnail_backup_path(post)):
        content = files.get(get_post_thumbnail_backup_path(post))
    else:
        content = files.get(get_post_content_path(post))
    try:
        image = images.Image(content)
        image.resize_fill(
            int(config.config['thumbnails']['post_width']),
            int(config.config['thumbnails']['post_height']))
        files.save(get_post_thumbnail_path(post), image.to_jpeg())
    except errors.ProcessingError:
        files.save(get_post_thumbnail_path(post), EMPTY_PIXEL)

def update_post_tags(post, tag_names):
    existing_tags, new_tags = tags.get_or_create_tags_by_names(tag_names)
    post.tags = existing_tags + new_tags

def update_post_relations(post, new_post_ids):
    old_posts = post.relations
    old_post_ids = [p.post_id for p in old_posts]
    new_posts = db.session \
        .query(db.Post) \
        .filter(db.Post.post_id.in_(new_post_ids)) \
        .all()
    if len(new_posts) != len(new_post_ids):
        raise InvalidPostRelationError('One of relations does not exist.')

    relations_to_del = [p for p in old_posts if p.post_id not in new_post_ids]
    relations_to_add = [p for p in new_posts if p.post_id not in old_post_ids]
    for relation in relations_to_del:
        post.relations.remove(relation)
        relation.relations.remove(post)
    for relation in relations_to_add:
        post.relations.append(relation)
        relation.relations.append(post)

def update_post_notes(post, notes):
    post.notes = []
    for note in notes:
        for field in ('polygon', 'text'):
            if field not in note:
                raise InvalidPostNoteError('Note is missing %r field.' % field)
        if not note['text']:
            raise InvalidPostNoteError('A note\'s text cannot be empty.')
        if len(note['polygon']) < 3:
            raise InvalidPostNoteError(
                'A note\'s polygon must have at least 3 points.')
        for point in note['polygon']:
            if len(point) != 2:
                raise InvalidPostNoteError(
                    'A point in note\'s polygon must have two coordinates.')
            try:
                pos_x = float(point[0])
                pos_y = float(point[1])
                if not 0 <= pos_x <= 1 or not 0 <= pos_y <= 1:
                    raise InvalidPostNoteError(
                        'A point in note\'s polygon must be in 0..1 range.')
            except ValueError:
                raise InvalidPostNoteError(
                    'A point in note\'s polygon must be numeric.')
        if util.value_exceeds_column_size(note['text'], db.PostNote.text):
            raise InvalidPostNoteError('Note text is too long.')
        post.notes.append(
            db.PostNote(polygon=note['polygon'], text=note['text']))

def update_post_flags(post, flags):
    target_flags = []
    for flag in flags:
        flag = util.flip(FLAG_MAP).get(flag, None)
        if not flag:
            raise InvalidPostFlagError(
                'Flag must be one of %r.' % list(FLAG_MAP.values()))
        target_flags.append(flag)
    post.flags = target_flags

def feature_post(post, user):
    post_feature = db.PostFeature()
    post_feature.time = datetime.datetime.utcnow()
    post_feature.post = post
    post_feature.user = user
    db.session.add(post_feature)
