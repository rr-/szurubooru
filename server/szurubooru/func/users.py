import datetime
import hashlib
import re
from sqlalchemy import func
from szurubooru import config, db, errors
from szurubooru.func import auth, util, files, images

class UserNotFoundError(errors.NotFoundError): pass
class UserAlreadyExistsError(errors.ValidationError): pass
class InvalidUserNameError(errors.ValidationError): pass
class InvalidEmailError(errors.ValidationError): pass
class InvalidPasswordError(errors.ValidationError): pass
class InvalidRankError(errors.ValidationError): pass
class InvalidAvatarError(errors.ValidationError): pass

def serialize_user(user, authenticated_user, force_show_email=False):
    if not user:
        return {}

    ret = {
        'name': user.name,
        'rank': user.rank,
        'rankName': config.config['rank_names'].get(user.rank, 'Unknown'),
        'creationTime': user.creation_time,
        'lastLoginTime': user.last_login_time,
        'avatarStyle': user.avatar_style,
        'email': user.email,
    }

    if user.avatar_style == user.AVATAR_GRAVATAR:
        md5 = hashlib.md5()
        md5.update((user.email or user.name).lower().encode('utf-8'))
        digest = md5.hexdigest()
        ret['avatarUrl'] = 'http://gravatar.com/avatar/%s?d=retro&s=%d' % (
            digest, config.config['thumbnails']['avatar_width'])
    else:
        ret['avatarUrl'] = '%s/avatars/%s.jpg' % (
            config.config['data_url'].rstrip('/'), user.name.lower())

    if authenticated_user.user_id != user.user_id \
        and not force_show_email \
        and not auth.has_privilege(authenticated_user, 'users:edit:any:email'):
        del ret['email']

    return ret

def serialize_user_with_details(user, authenticated_user, **kwargs):
    return {'user': serialize_user(user, authenticated_user, **kwargs)}

def get_user_count():
    return db.session.query(db.User).count()

def try_get_user_by_name(name):
    return db.session \
        .query(db.User) \
        .filter(func.lower(db.User.name) == func.lower(name)) \
        .one_or_none()

def get_user_by_name(name):
    user = try_get_user_by_name(name)
    if not user:
        raise UserNotFoundError('User %r not found.' % name)
    return user

def try_get_user_by_name_or_email(name_or_email):
    return db.session \
        .query(db.User) \
        .filter(
            (func.lower(db.User.name) == func.lower(name_or_email))
            | (func.lower(db.User.email) == func.lower(name_or_email))) \
        .one_or_none()

def get_user_by_name_or_email(name_or_email):
    user = try_get_user_by_name_or_email(name_or_email)
    if not user:
        raise UserNotFoundError('User %r not found.' % name_or_email)
    return user

def create_user(name, password, email, auth_user):
    user = db.User()
    update_user_name(user, name, auth_user)
    update_user_password(user, password)
    update_user_email(user, email)
    if get_user_count() > 0:
        user.rank = config.config['default_rank']
    else:
        user.rank = 'admin'
    user.creation_time = datetime.datetime.now()
    user.avatar_style = db.User.AVATAR_GRAVATAR
    return user

def update_user_name(user, name, auth_user):
    if not name:
        raise InvalidUserNameError('Name cannot be empty.')
    if util.value_exceeds_column_size(name, db.User.name):
        raise InvalidUserNameError('User name is too long.')
    other_user = try_get_user_by_name(name)
    if other_user and other_user.user_id != auth_user.user_id:
        raise UserAlreadyExistsError('User %r already exists.' % name)
    name = name.strip()
    name_regex = config.config['user_name_regex']
    if not re.match(name_regex, name):
        raise InvalidUserNameError(
            'User name %r must satisfy regex %r.' % (name, name_regex))
    user.name = name

def update_user_password(user, password):
    if not password:
        raise InvalidPasswordError('Password cannot be empty.')
    password_regex = config.config['password_regex']
    if not re.match(password_regex, password):
        raise InvalidPasswordError(
            'Password must satisfy regex %r.' % password_regex)
    user.password_salt = auth.create_password()
    user.password_hash = auth.get_password_hash(user.password_salt, password)

def update_user_email(user, email):
    if email:
        email = email.strip()
    if not email:
        email = None
    if email and util.value_exceeds_column_size(email, db.User.email):
        raise InvalidEmailError('Email is too long.')
    if not util.is_valid_email(email):
        raise InvalidEmailError('E-mail is invalid.')
    user.email = email

def update_user_rank(user, rank, authenticated_user):
    if not rank:
        raise InvalidRankError('Rank cannot be empty.')
    rank = rank.strip()
    available_ranks = config.config['ranks']
    if not rank in available_ranks:
        raise InvalidRankError(
            'Rank %r is invalid. Valid ranks: %r' % (rank, available_ranks))
    if available_ranks.index(authenticated_user.rank) \
            < available_ranks.index(rank) and get_user_count() > 0:
        raise errors.AuthError('Trying to set higher rank than your own.')
    user.rank = rank

def update_user_avatar(user, avatar_style, avatar_content):
    if avatar_style == 'gravatar':
        user.avatar_style = user.AVATAR_GRAVATAR
    elif avatar_style == 'manual':
        user.avatar_style = user.AVATAR_MANUAL
        if not avatar_content:
            raise InvalidAvatarError('Avatar content missing.')
        image = images.Image(avatar_content)
        image.resize_fill(
            int(config.config['thumbnails']['avatar_width']),
            int(config.config['thumbnails']['avatar_height']))
        files.save('avatars/' + user.name.lower() + '.jpg', image.to_jpeg())
    else:
        raise InvalidAvatarError(
            'Avatar style %r is invalid. Valid avatar styles: %r.' % (
                avatar_style, ['gravatar', 'manual']))

def bump_user_login_time(user):
    user.last_login_time = datetime.datetime.now()

def reset_user_password(user):
    password = auth.create_password()
    user.password_salt = auth.create_password()
    user.password_hash = auth.get_password_hash(user.password_salt, password)
    return password
