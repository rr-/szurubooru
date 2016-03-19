import falcon
from szurubooru.model.user import User

class AuthService(object):
    def __init__(self, config, user_service):
        self._config = config
        self._user_service = user_service

    def authenticate(self, username, password):
        if not username:
            return self._create_anonymous_user()
        user = self._user_service.get_by_name(username)
        if not user:
            raise falcon.HTTPForbidden(
                'Authentication failed', 'No such user.')
        if not user.has_password(password):
            raise falcon.HTTPForbidden(
                'Authentication failed', 'Invalid password.')
        return user

    def verify_privilege(self, user, privilege_name):
        all_ranks = ['anonymous'] \
            + self._config['service']['user_ranks'] \
            + ['admin', 'nobody']

        assert privilege_name in self._config['privileges']
        assert user.rank in all_ranks
        minimal_rank = self._config['privileges'][privilege_name]
        good_ranks = all_ranks[all_ranks.index(minimal_rank):]
        if user.rank not in good_ranks:
            raise falcon.HTTPForbidden(
                'Authentication failed', 'Insufficient privileges to do this.')

    def _create_anonymous_user(self):
        user = User()
        user.name = None
        user.rank = 'anonymous'
        user.password = None
        return user
