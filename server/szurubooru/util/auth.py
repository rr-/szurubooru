import hashlib
import random
from szurubooru import config
from szurubooru import errors

def get_password_hash(salt, password):
    ''' Retrieve new-style password hash. '''
    digest = hashlib.sha256()
    digest.update(config.config['basic']['secret'].encode('utf8'))
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
    ''' Create an easy-to-remember password. '''
    alphabet = {
        'c': list('bcdfghijklmnpqrstvwxyz'),
        'v': list('aeiou'),
        'n': list('0123456789'),
    }
    pattern = 'cvcvnncvcv'
    return ''.join(random.choice(alphabet[l]) for l in list(pattern))

def is_valid_password(user, password):
    ''' Return whether the given password for a given user is valid. '''
    salt, valid_hash = user.password_salt, user.password_hash
    possible_hashes = [
        get_password_hash(salt, password),
        get_legacy_password_hash(salt, password)
    ]
    return valid_hash in possible_hashes

def verify_privilege(user, privilege_name):
    '''
    Throw an AuthError if the given user doesn't have given privilege.
    '''
    all_ranks = config.config['service']['user_ranks']

    assert privilege_name in config.config['privileges']
    assert user.access_rank in all_ranks
    minimal_rank = config.config['privileges'][privilege_name]
    good_ranks = all_ranks[all_ranks.index(minimal_rank):]
    if user.access_rank not in good_ranks:
        raise errors.AuthError('Insufficient privileges to do this.')

def generate_authentication_token(user):
    ''' Generate nonguessable challenge (e.g. links in password reminder). '''
    digest = hashlib.md5()
    digest.update(config.config['basic']['secret'].encode('utf8'))
    digest.update(user.password_salt.encode('utf8'))
    return digest.hexdigest()
