from datetime import datetime
from szurubooru import errors, search
from szurubooru.tests.database_test_case import DatabaseTestCase
from szurubooru.tests.api import util

class TestUserSearchExecutor(DatabaseTestCase):
    def setUp(self):
        super().setUp()
        self.search_config = search.UserSearchConfig()
        self.executor = search.SearchExecutor(self.search_config)

    def _test(self, query, page, page_size, expected_count, expected_user_names):
        count, users = self.executor.execute(self.session, query, page, page_size)
        self.assertEqual(count, expected_count)
        self.assertEqual([u.name for u in users], expected_user_names)

    def _test_raises(self, query, page, page_size):
        self.assertRaises(
            errors.SearchError,
            self.executor.execute,
            self.session,
            query,
            page,
            page_size)

    def test_filter_by_creation_time(self):
        user1 = util.mock_user('u1')
        user2 = util.mock_user('u2')
        user1.creation_time = datetime(2014, 1, 1)
        user2.creation_time = datetime(2015, 1, 1)
        self.session.add_all([user1, user2])
        for alias in ['creation-time', 'creation-date']:
            self._test('%s:2014' % alias, 1, 100, 1, ['u1'])

    def test_filter_by_negated_creation_time(self):
        user1 = util.mock_user('u1')
        user2 = util.mock_user('u2')
        user1.creation_time = datetime(2014, 1, 1)
        user2.creation_time = datetime(2015, 1, 1)
        self.session.add_all([user1, user2])
        for alias in ['creation-time', 'creation-date']:
            self._test('-%s:2014' % alias, 1, 100, 1, ['u2'])

    def test_filter_by_ranged_creation_time(self):
        user1 = util.mock_user('u1')
        user2 = util.mock_user('u2')
        user3 = util.mock_user('u3')
        user1.creation_time = datetime(2014, 1, 1)
        user2.creation_time = datetime(2014, 6, 1)
        user3.creation_time = datetime(2015, 1, 1)
        self.session.add_all([user1, user2, user3])
        for alias in ['creation-time', 'creation-date']:
            self._test('%s:2014..2014-06' % alias, 1, 100, 2, ['u1', 'u2'])
            self._test('%s:2014-06..2015-01-01' % alias, 1, 100, 2, ['u2', 'u3'])
            self._test('%s:2014-06..' % alias, 1, 100, 2, ['u2', 'u3'])
            self._test('%s:..2014-06' % alias, 1, 100, 2, ['u1', 'u2'])
            self._test_raises('%s:..' % alias, 1, 100)

    def test_filter_by_negated_ranged_creation_time(self):
        user1 = util.mock_user('u1')
        user2 = util.mock_user('u2')
        user3 = util.mock_user('u3')
        user1.creation_time = datetime(2014, 1, 1)
        user2.creation_time = datetime(2014, 6, 1)
        user3.creation_time = datetime(2015, 1, 1)
        self.session.add_all([user1, user2, user3])
        for alias in ['creation-time', 'creation-date']:
            self._test('-%s:2014..2014-06' % alias, 1, 100, 1, ['u3'])
            self._test('-%s:2014-06..2015-01-01' % alias, 1, 100, 1, ['u1'])

    def test_filter_by_composite_creation_time(self):
        user1 = util.mock_user('u1')
        user2 = util.mock_user('u2')
        user3 = util.mock_user('u3')
        user1.creation_time = datetime(2014, 1, 1)
        user2.creation_time = datetime(2014, 6, 1)
        user3.creation_time = datetime(2015, 1, 1)
        self.session.add_all([user1, user2, user3])
        for alias in ['creation-time', 'creation-date']:
            self._test('%s:2014-01,2015' % alias, 1, 100, 2, ['u1', 'u3'])

    def test_filter_by_negated_composite_creation_time(self):
        user1 = util.mock_user('u1')
        user2 = util.mock_user('u2')
        user3 = util.mock_user('u3')
        user1.creation_time = datetime(2014, 1, 1)
        user2.creation_time = datetime(2014, 6, 1)
        user3.creation_time = datetime(2015, 1, 1)
        self.session.add_all([user1, user2, user3])
        for alias in ['creation-time', 'creation-date']:
            self._test('-%s:2014-01,2015' % alias, 1, 100, 1, ['u2'])

    def test_filter_by_name(self):
        self.session.add(util.mock_user('u1'))
        self.session.add(util.mock_user('u2'))
        self._test('name:u1', 1, 100, 1, ['u1'])
        self._test('name:u2', 1, 100, 1, ['u2'])

    def test_filter_by_name_wildcards(self):
        self.session.add(util.mock_user('user1'))
        self.session.add(util.mock_user('user2'))
        self._test('name:*1', 1, 100, 1, ['user1'])
        self._test('name:*2', 1, 100, 1, ['user2'])
        self._test('name:*', 1, 100, 2, ['user1', 'user2'])
        self._test('name:u*', 1, 100, 2, ['user1', 'user2'])
        self._test('name:*ser*', 1, 100, 2, ['user1', 'user2'])
        self._test('name:*zer*', 1, 100, 0, [])
        self._test('name:zer*', 1, 100, 0, [])
        self._test('name:*zer', 1, 100, 0, [])

    def test_filter_by_negated_name(self):
        self.session.add(util.mock_user('u1'))
        self.session.add(util.mock_user('u2'))
        self._test('-name:u1', 1, 100, 1, ['u2'])
        self._test('-name:u2', 1, 100, 1, ['u1'])

    def test_filter_by_composite_name(self):
        self.session.add(util.mock_user('u1'))
        self.session.add(util.mock_user('u2'))
        self.session.add(util.mock_user('u3'))
        self._test('name:u1,u2', 1, 100, 2, ['u1', 'u2'])

    def test_filter_by_negated_composite_name(self):
        self.session.add(util.mock_user('u1'))
        self.session.add(util.mock_user('u2'))
        self.session.add(util.mock_user('u3'))
        self._test('-name:u1,u3', 1, 100, 1, ['u2'])

    def test_filter_by_ranged_name(self):
        self._test_raises('name:u1..u2', 1, 100)

    def test_paging(self):
        self.session.add(util.mock_user('u1'))
        self.session.add(util.mock_user('u2'))
        self._test('', 1, 1, 2, ['u1'])
        self._test('', 2, 1, 2, ['u2'])

    def test_order_by_name(self):
        self.session.add(util.mock_user('u2'))
        self.session.add(util.mock_user('u1'))
        self._test('', 1, 100, 2, ['u1', 'u2'])
        self._test('order:name', 1, 100, 2, ['u1', 'u2'])
        self._test('-order:name', 1, 100, 2, ['u2', 'u1'])
        self._test('order:name,asc', 1, 100, 2, ['u1', 'u2'])
        self._test('order:name,desc', 1, 100, 2, ['u2', 'u1'])
        self._test('-order:name,asc', 1, 100, 2, ['u2', 'u1'])
        self._test('-order:name,desc', 1, 100, 2, ['u1', 'u2'])

    def test_invalid_tokens(self):
        for query in [
                'order:',
                'order:nam',
                'order:name,as',
                'order:name,asc,desc',
                'bad:x',
                'special:unsupported']:
            self._test_raises(query, 1, 100)

    def test_anonymous(self):
        self.session.add(util.mock_user('u1'))
        self.session.add(util.mock_user('u2'))
        self._test('u1', 1, 100, 1, ['u1'])
        self._test('u2', 1, 100, 1, ['u2'])

    def test_empty_search(self):
        self.session.add(util.mock_user('u1'))
        self.session.add(util.mock_user('u2'))
        self._test('', 1, 100, 2, ['u1', 'u2'])

    def test_negated_anonymous(self):
        self.session.add(util.mock_user('u1'))
        self.session.add(util.mock_user('u2'))
        self._test('-u1', 1, 100, 1, ['u2'])
        self._test('-u2', 1, 100, 1, ['u1'])

    def test_combining(self):
        user1 = util.mock_user('u1')
        user2 = util.mock_user('u2')
        user3 = util.mock_user('u3')
        user1.creation_time = datetime(2014, 1, 1)
        user2.creation_time = datetime(2014, 6, 1)
        user3.creation_time = datetime(2015, 1, 1)
        self.session.add_all([user1, user2, user3])
        self._test('creation-time:2014 u1', 1, 100, 1, ['u1'])
        self._test('creation-time:2014 u2', 1, 100, 1, ['u2'])
        self._test('creation-time:2016 u2', 1, 100, 0, [])

    def test_special(self):
        self._test_raises('special:-', 1, 100)
