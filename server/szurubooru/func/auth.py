import hashlib
import random
from szurubooru import config, db, errors

def get_password_hash(salt, password):
    ''' Retrieve new-style password hash. '''
    digest = hashlib.sha256()
    digest.update(config.config['secret'].encode('utf8'))
    digest.update(salt.encode('utf8'))
    digest.update(password.encode('utf8'))
    return digest.hexdigest()

def get_legacy_password_hash(salt, password):
    ''' Retrieve old-style password hash. '''
    digest = hashlib.sha1()
    digest.update(b'1A2/$_4xVa')
    digest.update(salt.encode('utf8'))
    digest.update(password.encode('utf8'))
    return digest.hexdigest()

def create_password():
    alphabet = {
        'c': list('bcdfghijklmnpqrstvwxyz'),
        'v': list('aeiou'),
        'n': list('0123456789'),
    }
    pattern = 'cvcvnncvcv'
    return ''.join(random.choice(alphabet[l]) for l in list(pattern))

def is_valid_password(user, password):
    salt, valid_hash = user.password_salt, user.password_hash
    possible_hashes = [
        get_password_hash(salt, password),
        get_legacy_password_hash(salt, password)
    ]
    return valid_hash in possible_hashes

def has_privilege(user, privilege_name):
    assert privilege_name in config.config['privileges']
    assert user.rank in db.User.ALL_RANKS
    minimal_rank = config.config['privileges'][privilege_name]
    good_ranks = db.User.ALL_RANKS[db.User.ALL_RANKS.index(minimal_rank):]
    return user.rank in good_ranks

def verify_privilege(user, privilege_name):
    if not has_privilege(user, privilege_name):
        raise errors.AuthError('Insufficient privileges to do this.')

def generate_authentication_token(user):
    ''' Generate nonguessable challenge (e.g. links in password reminder). '''
    digest = hashlib.md5()
    digest.update(config.config['secret'].encode('utf8'))
    digest.update(user.password_salt.encode('utf8'))
    return digest.hexdigest()
