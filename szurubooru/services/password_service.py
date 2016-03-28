''' Exports PasswordService. '''

import hashlib
import random

class PasswordService(object):
    ''' Stateless utilities for passwords '''

    def __init__(self, config):
        self._config = config

    def get_password_hash(self, salt, password):
        ''' Retrieves new-style password hash. '''
        digest = hashlib.sha256()
        digest.update(self._config['basic']['secret'].encode('utf8'))
        digest.update(salt.encode('utf8'))
        digest.update(password.encode('utf8'))
        return digest.hexdigest()

    def get_legacy_password_hash(self, salt, password):
        ''' Retrieves old-style password hash. '''
        digest = hashlib.sha1()
        digest.update(b'1A2/$_4xVa')
        digest.update(salt.encode('utf8'))
        digest.update(password.encode('utf8'))
        return digest.hexdigest()

    def create_password(self):
        ''' Creates an easy-to-remember password. '''
        alphabet = {
            'c': list('bcdfghijklmnpqrstvwxyz'),
            'v': list('aeiou'),
            'n': list('0123456789'),
        }
        pattern = 'cvcvnncvcv'
        return ''.join(random.choice(alphabet[l]) for l in list(pattern))
