''' Exports AuthService. '''

from szurubooru.services.errors import AuthError

class AuthService(object):
    ''' Services related to user authentication '''

    def __init__(self, config, password_service):
        self._config = config
        self._password_service = password_service

    def is_valid_password(self, user, password):
        ''' Returns whether the given password for a given user is valid. '''
        salt, valid_hash = user.password_salt, user.password_hash
        possible_hashes = [
            self._password_service.get_password_hash(salt, password),
            self._password_service.get_legacy_password_hash(salt, password)
        ]
        return valid_hash in possible_hashes

    def verify_privilege(self, user, privilege_name):
        '''
        Throws an AuthError if the given user doesn't have given privilege.
        '''
        all_ranks = ['anonymous'] \
            + self._config['service']['user_ranks'] \
            + ['admin', 'nobody']

        assert privilege_name in self._config['privileges']
        assert user.access_rank in all_ranks
        minimal_rank = self._config['privileges'][privilege_name]
        good_ranks = all_ranks[all_ranks.index(minimal_rank):]
        if user.access_rank not in good_ranks:
            raise AuthError('Insufficient privileges to do this.')
