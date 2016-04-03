from datetime import datetime
import szurubooru.services
from szurubooru.api.user_api import UserDetailApi
from szurubooru.errors import AuthError, ValidationError
from szurubooru.model.user import User
from szurubooru.tests.database_test_case import DatabaseTestCase
from szurubooru.util import dotdict

class TestUserDetailApi(DatabaseTestCase):
    def setUp(self):
        super().setUp()
        config = {
            'basic': {
                'secret': '',
            },
            'service': {
                'user_name_regex': '.{3,}',
                'password_regex': '.{3,}',
                'user_ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
            },
            'privileges': {
                'users:edit:self:name': 'regular_user',
                'users:edit:self:pass': 'regular_user',
                'users:edit:self:email': 'regular_user',
                'users:edit:self:rank': 'mod',

                'users:edit:any:name': 'mod',
                'users:edit:any:pass': 'mod',
                'users:edit:any:email': 'mod',
                'users:edit:any:rank': 'admin',
            }
        }
        password_service = szurubooru.services.PasswordService(config)
        auth_service = szurubooru.services.AuthService(config, password_service)
        user_service = szurubooru.services.UserService(config, password_service)
        self.auth_service = auth_service
        self.api = UserDetailApi(
            config, auth_service, password_service, user_service)
        self.context = dotdict()
        self.context.session = self.session
        self.context.request = {}
        self.request = dotdict()
        self.request.context = self.context

    def _create_user(self, name, rank='admin'):
        user = User()
        user.name = name
        user.password = 'dummy'
        user.password_salt = 'dummy'
        user.password_hash = 'dummy'
        user.email = 'dummy'
        user.access_rank = rank
        user.creation_time = datetime.now()
        user.avatar_style = User.AVATAR_GRAVATAR
        return user

    def test_updating_nothing(self):
        admin_user = self._create_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.api.put(self.request, self.context, 'u1')
        admin_user = self.session.query(User).filter_by(name='u1').one()
        self.assertEqual(admin_user.name, 'u1')
        self.assertEqual(admin_user.email, 'dummy')
        self.assertEqual(admin_user.access_rank, 'admin')

    def test_admin_updating_everything_for_themselves(self):
        admin_user = self._create_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.context.request = {
            'name': 'chewie',
            'email': 'asd@asd.asd',
            'password': 'valid',
            'accessRank': 'mod',
        }
        self.api.put(self.request, self.context, 'u1')
        admin_user = self.session.query(User).filter_by(name='chewie').one()
        self.assertEqual(admin_user.name, 'chewie')
        self.assertEqual(admin_user.email, 'asd@asd.asd')
        self.assertEqual(admin_user.access_rank, 'mod')
        self.assertTrue(self.auth_service.is_valid_password(admin_user, 'valid'))
        self.assertFalse(self.auth_service.is_valid_password(admin_user, 'invalid'))

    def test_removing_email(self):
        admin_user = self._create_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.context.request = {'email': ''}
        self.api.put(self.request, self.context, 'u1')
        admin_user = self.session.query(User).filter_by(name='u1').one()
        self.assertEqual(admin_user.email, None)

    def test_invalid_inputs(self):
        admin_user = self._create_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.context.request = {'name': '.'}
        self.assertRaises(
            ValidationError, self.api.put, self.request, self.context, 'u1')
        self.context.request = {'password': '.'}
        self.assertRaises(
            ValidationError, self.api.put, self.request, self.context, 'u1')
        self.context.request = {'accessRank': '.'}
        self.assertRaises(
            ValidationError, self.api.put, self.request, self.context, 'u1')
        self.context.request = {'email': '.'}
        self.assertRaises(
            ValidationError, self.api.put, self.request, self.context, 'u1')

    def test_user_trying_to_update_someone_else(self):
        user1 = self._create_user('u1', 'regular_user')
        user2 = self._create_user('u2', 'regular_user')
        self.session.add_all([user1, user2])
        self.context.user = user1
        for request in [
                {'name': 'whatever'},
                {'email': 'whatever'},
                {'accessRank': 'whatever'},
                {'password': 'whatever'}]:
            self.context.request = request
            self.assertRaises(
                AuthError, self.api.put, self.request, self.context, user2.name)

    def test_user_trying_to_become_someone_else(self):
        user1 = self._create_user('u1', 'regular_user')
        user2 = self._create_user('u2', 'regular_user')
        self.session.add_all([user1, user2])
        self.context.user = user1
        self.context.request = {'name': 'u2'}
        self.assertRaises(
            ValidationError, self.api.put, self.request, self.context, 'u1')

    def test_mods_trying_to_become_admin(self):
        user1 = self._create_user('u1', 'mod')
        user2 = self._create_user('u2', 'mod')
        self.session.add_all([user1, user2])
        self.context.user = user1
        self.context.request = {'accessRank': 'admin'}
        self.assertRaises(
            AuthError, self.api.put, self.request, self.context, user1.name)
        self.assertRaises(
            AuthError, self.api.put, self.request, self.context, user2.name)
