from datetime import datetime
from szurubooru import api, db, errors, config
from szurubooru.util import auth, misc
from szurubooru.tests.database_test_case import DatabaseTestCase

class TestUserDetailApi(DatabaseTestCase):
    def setUp(self):
        super().setUp()
        config_mock = {
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
        self.old_config = config.config
        config.config = config_mock
        self.api = api.UserDetailApi()
        self.context = misc.dotdict()
        self.context.session = self.session
        self.context.request = {}

    def tearDown(self):
        config.config = self.old_config

    def _create_user(self, name, rank='admin'):
        user = db.User()
        user.name = name
        user.password = 'dummy'
        user.password_salt = 'dummy'
        user.password_hash = 'dummy'
        user.email = 'dummy'
        user.access_rank = rank
        user.creation_time = datetime.now()
        user.avatar_style = db.User.AVATAR_GRAVATAR
        return user

    def test_updating_nothing(self):
        admin_user = self._create_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.api.put(self.context, 'u1')
        admin_user = self.session.query(db.User).filter_by(name='u1').one()
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
            'password': 'oks',
            'accessRank': 'mod',
        }
        self.api.put(self.context, 'u1')
        admin_user = self.session.query(db.User).filter_by(name='chewie').one()
        self.assertEqual(admin_user.name, 'chewie')
        self.assertEqual(admin_user.email, 'asd@asd.asd')
        self.assertEqual(admin_user.access_rank, 'mod')
        self.assertTrue(auth.is_valid_password(admin_user, 'oks'))
        self.assertFalse(auth.is_valid_password(admin_user, 'invalid'))

    def test_removing_email(self):
        admin_user = self._create_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.context.request = {'email': ''}
        self.api.put(self.context, 'u1')
        admin_user = self.session.query(db.User).filter_by(name='u1').one()
        self.assertEqual(admin_user.email, None)

    def test_invalid_inputs(self):
        admin_user = self._create_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.context.request = {'name': '.'}
        self.assertRaises(
            errors.ValidationError, self.api.put, self.context, 'u1')
        self.context.request = {'password': '.'}
        self.assertRaises(
            errors.ValidationError, self.api.put, self.context, 'u1')
        self.context.request = {'accessRank': '.'}
        self.assertRaises(
            errors.ValidationError, self.api.put, self.context, 'u1')
        self.context.request = {'email': '.'}
        self.assertRaises(
            errors.ValidationError, self.api.put, self.context, 'u1')

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
                errors.AuthError, self.api.put, self.context, user2.name)

    def test_user_trying_to_become_someone_else(self):
        user1 = self._create_user('u1', 'regular_user')
        user2 = self._create_user('u2', 'regular_user')
        self.session.add_all([user1, user2])
        self.context.user = user1
        self.context.request = {'name': 'u2'}
        self.assertRaises(
            errors.ValidationError, self.api.put, self.context, 'u1')

    def test_mods_trying_to_become_admin(self):
        user1 = self._create_user('u1', 'mod')
        user2 = self._create_user('u2', 'mod')
        self.session.add_all([user1, user2])
        self.context.user = user1
        self.context.request = {'accessRank': 'admin'}
        self.assertRaises(
            errors.AuthError, self.api.put, self.context, user1.name)
        self.assertRaises(
            errors.AuthError, self.api.put, self.context, user2.name)
