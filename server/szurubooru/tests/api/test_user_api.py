from datetime import datetime
from szurubooru import api, db, errors
from szurubooru.util import auth
from szurubooru.tests.database_test_case import DatabaseTestCase
from szurubooru.tests.api import util

class TestRetrievingUsers(DatabaseTestCase):
    def setUp(self):
        super().setUp()
        util.mock_config({
            'privileges': {
                'users:list': 'regular_user',
                'users:view': 'regular_user',
                'users:create': 'regular_user',
            },
            'avatar_thumbnail_size': 200,
            'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
            'rank_names': {},
        })
        util.mock_context(self)

    def test_retrieving_multiple(self):
        user1 = util.mock_user('u1', 'mod')
        user2 = util.mock_user('u2', 'mod')
        self.session.add_all([user1, user2])
        util.mock_params(self.context, {'query': '', 'page': 1})
        self.context.user.rank = 'regular_user'
        api_ = api.UserListApi()
        result = api_.get(self.context)
        self.assertEqual(result['query'], '')
        self.assertEqual(result['page'], 1)
        self.assertEqual(result['page_size'], 100)
        self.assertEqual(result['total'], 2)
        self.assertEqual([u['name'] for u in result['users']], ['u1', 'u2'])

    def test_retrieving_multiple_without_privileges(self):
        self.context.user.rank = 'anonymous'
        util.mock_params(self.context, {'query': '', 'page': 1})
        api_ = api.UserListApi()
        self.assertRaises(errors.AuthError, api_.get, self.context)

    def test_retrieving_single(self):
        user = util.mock_user('u1', 'regular_user')
        self.session.add(user)
        self.context.user.rank = 'regular_user'
        util.mock_params(self.context, {'query': '', 'page': 1})
        api_ = api.UserDetailApi()
        result = api_.get(self.context, 'u1')
        self.assertEqual(result['user']['id'], user.user_id)
        self.assertEqual(result['user']['name'], 'u1')
        self.assertEqual(result['user']['rank'], 'regular_user')
        self.assertEqual(result['user']['creationTime'], datetime(1997, 1, 1))
        self.assertEqual(result['user']['lastLoginTime'], None)
        self.assertEqual(result['user']['avatarStyle'], 1) # i.e. integer

    def test_retrieving_non_existing(self):
        self.context.user.rank = 'regular_user'
        util.mock_params(self.context, {'query': '', 'page': 1})
        api_ = api.UserDetailApi()
        self.assertRaises(errors.NotFoundError, api_.get, self.context, '-')

    def test_retrieving_single_without_privileges(self):
        self.context.user.rank = 'anonymous'
        util.mock_params(self.context, {'query': '', 'page': 1})
        api_ = api.UserDetailApi()
        self.assertRaises(errors.AuthError, api_.get, self.context, '-')

class TestCreatingUser(DatabaseTestCase):
    def setUp(self):
        super().setUp()
        util.mock_config({
            'secret': '',
            'user_name_regex': '.{3,}',
            'password_regex': '.{3,}',
            'default_rank': 'regular_user',
            'avatar_thumbnail_size': 200,
            'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
            'rank_names': {},
            'privileges': {
                'users:create': 'anonymous',
            },
        })
        self.api = api.UserListApi()
        util.mock_context(self)
        self.context.user.rank = 'anonymous'

    def test_creating_valid_user(self):
        self.context.request = {
            'name': 'chewie',
            'email': 'asd@asd.asd',
            'password': 'oks',
        }
        self.api.post(self.context)
        created_user = self.session.query(db.User).filter_by(name='chewie').one()
        self.assertEqual(created_user.name, 'chewie')
        self.assertEqual(created_user.email, 'asd@asd.asd')
        self.assertEqual(created_user.rank, 'regular_user')
        self.assertTrue(auth.is_valid_password(created_user, 'oks'))
        self.assertFalse(auth.is_valid_password(created_user, 'invalid'))

    def test_creating_user_that_already_exists(self):
        self.context.request = {
            'name': 'chewie',
            'email': 'asd@asd.asd',
            'password': 'oks',
        }
        self.api.post(self.context)
        self.assertRaises(errors.IntegrityError, self.api.post, self.context)

    def test_creating_user_that_already_exists_insensitive(self):
        self.context.request = {
            'name': 'chewie',
            'email': 'asd@asd.asd',
            'password': 'oks',
        }
        self.api.post(self.context)
        self.context.name = 'chewie'
        self.assertRaises(errors.IntegrityError, self.api.post, self.context)

    def test_missing_field(self):
        for key in ['name', 'email', 'password']:
            self.context.request = {
                'name': 'chewie',
                'email': 'asd@asd.asd',
                'password': 'oks',
            }
            del self.context.request[key]
            self.assertRaises(errors.ValidationError, self.api.post, self.context)

class TestUpdatingUser(DatabaseTestCase):
    def setUp(self):
        super().setUp()
        util.mock_config({
            'secret': '',
            'user_name_regex': '.{3,}',
            'password_regex': '.{3,}',
            'avatar_thumbnail_size': 200,
            'ranks': ['anonymous', 'regular_user', 'mod', 'admin'],
            'rank_names': {},
            'privileges': {
                'users:edit:self:name': 'regular_user',
                'users:edit:self:pass': 'regular_user',
                'users:edit:self:email': 'regular_user',
                'users:edit:self:rank': 'mod',

                'users:edit:any:name': 'mod',
                'users:edit:any:pass': 'mod',
                'users:edit:any:email': 'mod',
                'users:edit:any:rank': 'admin',
            },
        })
        util.mock_context(self)
        self.api = api.UserDetailApi()

    def test_update_changing_nothing(self):
        admin_user = util.mock_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.api.put(self.context, 'u1')
        admin_user = self.session.query(db.User).filter_by(name='u1').one()
        self.assertEqual(admin_user.name, 'u1')
        self.assertEqual(admin_user.email, 'dummy')
        self.assertEqual(admin_user.rank, 'admin')

    def test_updating_non_existing_user(self):
        admin_user = util.mock_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.assertRaises(errors.NotFoundError, self.api.put, self.context, 'u2')

    def test_admin_updating_everything_for_themselves(self):
        admin_user = util.mock_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.context.request = {
            'name': 'chewie',
            'email': 'asd@asd.asd',
            'password': 'oks',
            'rank': 'mod',
        }
        self.api.put(self.context, 'u1')
        admin_user = self.session.query(db.User).filter_by(name='chewie').one()
        self.assertEqual(admin_user.name, 'chewie')
        self.assertEqual(admin_user.email, 'asd@asd.asd')
        self.assertEqual(admin_user.rank, 'mod')
        self.assertTrue(auth.is_valid_password(admin_user, 'oks'))
        self.assertFalse(auth.is_valid_password(admin_user, 'invalid'))

    def test_removing_email(self):
        admin_user = util.mock_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.context.request = {'email': ''}
        self.api.put(self.context, 'u1')
        admin_user = self.session.query(db.User).filter_by(name='u1').one()
        self.assertEqual(admin_user.email, None)

    def test_invalid_inputs(self):
        admin_user = util.mock_user('u1', 'admin')
        self.session.add(admin_user)
        self.context.user = admin_user
        self.context.request = {'name': '.'}
        self.assertRaises(
            errors.ValidationError, self.api.put, self.context, 'u1')
        self.context.request = {'password': '.'}
        self.assertRaises(
            errors.ValidationError, self.api.put, self.context, 'u1')
        self.context.request = {'rank': '.'}
        self.assertRaises(
            errors.ValidationError, self.api.put, self.context, 'u1')
        self.context.request = {'email': '.'}
        self.assertRaises(
            errors.ValidationError, self.api.put, self.context, 'u1')

    def test_user_trying_to_update_someone_else(self):
        user1 = util.mock_user('u1', 'regular_user')
        user2 = util.mock_user('u2', 'regular_user')
        self.session.add_all([user1, user2])
        self.context.user = user1
        for request in [
                {'name': 'whatever'},
                {'email': 'whatever'},
                {'rank': 'whatever'},
                {'password': 'whatever'}]:
            self.context.request = request
            self.assertRaises(
                errors.AuthError, self.api.put, self.context, user2.name)

    def test_user_trying_to_become_someone_else(self):
        user1 = util.mock_user('me', 'regular_user')
        user2 = util.mock_user('her', 'regular_user')
        self.session.add_all([user1, user2])
        self.context.user = user1
        self.context.request = {'name': 'her'}
        self.assertRaises(
            errors.IntegrityError, self.api.put, self.context, 'me')
        self.session.rollback()

    def test_user_trying_to_become_someone_else_insensitive(self):
        user1 = util.mock_user('me', 'regular_user')
        user2 = util.mock_user('her', 'regular_user')
        self.session.add_all([user1, user2])
        self.context.user = user1
        self.context.request = {'name': 'HER'}
        self.assertRaises(
            errors.IntegrityError, self.api.put, self.context, 'me')
        self.session.rollback()

    def test_mods_trying_to_become_admin(self):
        user1 = util.mock_user('u1', 'mod')
        user2 = util.mock_user('u2', 'mod')
        self.session.add_all([user1, user2])
        self.context.user = user1
        self.context.request = {'rank': 'admin'}
        self.assertRaises(
            errors.AuthError, self.api.put, self.context, user1.name)
        self.assertRaises(
            errors.AuthError, self.api.put, self.context, user2.name)
