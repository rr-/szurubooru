import re
from datetime import datetime
from sqlalchemy import func
from szurubooru import config, db, errors
from szurubooru.util import auth, misc, files, images

def create_user(session, name, password, email):
    user = db.User()
    update_name(user, name)
    update_password(user, password)
    update_email(user, email)
    if not session.query(db.User).count():
        user.rank = 'admin'
    else:
        user.rank = config.config['default_rank']
    user.creation_time = datetime.now()
    user.avatar_style = db.User.AVATAR_GRAVATAR
    return user

def update_name(user, name):
    name = name.strip()
    name_regex = config.config['user_name_regex']
    if not re.match(name_regex, name):
        raise errors.ValidationError(
            'Name must satisfy regex %r.' % name_regex)
    user.name = name

def update_password(user, password):
    password_regex = config.config['password_regex']
    if not re.match(password_regex, password):
        raise errors.ValidationError(
            'Password must satisfy regex %r.' % password_regex)
    user.password_salt = auth.create_password()
    user.password_hash = auth.get_password_hash(user.password_salt, password)

def update_email(user, email):
    email = email.strip() or None
    if not misc.is_valid_email(email):
        raise errors.ValidationError(
            '%r is not a vaild email address.' % email)
    user.email = email

def update_rank(user, rank, authenticated_user):
    rank = rank.strip()
    available_ranks = config.config['ranks']
    if not rank in available_ranks:
        raise errors.ValidationError(
            'Bad rank %r. Valid ranks: %r' % (rank, available_ranks))
    if available_ranks.index(authenticated_user.rank) \
            < available_ranks.index(rank):
        raise errors.AuthError('Trying to set higher rank than your own.')
    user.rank = rank

def update_avatar(user, avatar_style, avatar_content):
    if avatar_style == 'gravatar':
        user.avatar_style = user.AVATAR_GRAVATAR
    elif avatar_style == 'manual':
        user.avatar_style = user.AVATAR_MANUAL
        if not avatar_content:
            raise errors.ValidationError('Avatar content missing.')
        image = images.Image(avatar_content)
        image.resize_fill(
            int(config.config['thumbnails']['avatar_width']),
            int(config.config['thumbnails']['avatar_height']))
        files.save('avatars/' + user.name.lower() + '.jpg', image.to_jpeg())
    else:
        raise errors.ValidationError('Unknown avatar style: %r' % avatar_style)

def bump_login_time(user):
    user.last_login_time = datetime.now()

def reset_password(user):
    password = auth.create_password()
    user.password_salt = auth.create_password()
    user.password_hash = auth.get_password_hash(user.password_salt, password)
    return password

def get_by_name(session, name):
    return session.query(db.User) \
        .filter(func.lower(db.User.name) == func.lower(name)) \
        .first()

def get_by_name_or_email(session, name_or_email):
    return session.query(db.User) \
        .filter(
            (func.lower(db.User.name) == func.lower(name_or_email))
            | (func.lower(db.User.email) == func.lower(name_or_email))) \
        .first()
