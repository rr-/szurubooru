import re
from datetime import datetime
from sqlalchemy import func
from szurubooru import config, db, errors
from szurubooru.util import auth, misc

def create_user(name, password, email):
    ''' Create an user with given parameters and returns it. '''
    user = db.User()
    update_name(user, name)
    update_password(user, password)
    update_email(user, email)
    user.rank = config.config['default_rank']
    user.creation_time = datetime.now()
    user.avatar_style = db.User.AVATAR_GRAVATAR
    return user

def update_name(user, name):
    ''' Validate and update user's name. '''
    name = name.strip()
    name_regex = config.config['user_name_regex']
    if not re.match(name_regex, name):
        raise errors.ValidationError(
            'Name must satisfy regex %r.' % name_regex)
    user.name = name

def update_password(user, password):
    ''' Validate and update user's password. '''
    password_regex = config.config['password_regex']
    if not re.match(password_regex, password):
        raise errors.ValidationError(
            'Password must satisfy regex %r.' % password_regex)
    user.password_salt = auth.create_password()
    user.password_hash = auth.get_password_hash(user.password_salt, password)

def update_email(user, email):
    ''' Validate and update user's email. '''
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
        raise errors.AuthError('Trying to set higher rank than your own')
    user.rank = rank

def bump_login_time(user):
    ''' Update user's login time to current date. '''
    user.last_login_time = datetime.now()

def reset_password(user):
    ''' Reset password for an user. '''
    password = auth.create_password()
    user.password_salt = auth.create_password()
    user.password_hash = auth.get_password_hash(user.password_salt, password)
    return password

def get_by_name(session, name):
    ''' Retrieve an user by its name. '''
    return session.query(db.User) \
        .filter(func.lower(db.User.name) == func.lower(name)) \
        .first()

def get_by_name_or_email(session, name_or_email):
    ''' Retrieve an user by its name or email. '''
    return session.query(db.User) \
        .filter(
            (func.lower(db.User.name) == func.lower(name_or_email))
            | (func.lower(db.User.email) == func.lower(name_or_email))) \
        .first()
