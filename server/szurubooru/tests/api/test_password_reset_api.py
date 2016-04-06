from unittest import mock
from szurubooru import api, errors
from szurubooru.util import auth, mailer
from szurubooru.tests.database_test_case import DatabaseTestCase
from szurubooru.tests.api import util

class TestPasswordReset(DatabaseTestCase):
    def setUp(self):
        super().setUp()
        util.mock_config({
            'secret': 'x',
            'base_url': 'http://example.com/',
            'name': 'Test instance',
        })
        util.mock_context(self)
        self.api = api.PasswordResetApi()

    def test_reset_non_existing(self):
        self.assertRaises(errors.NotFoundError, self.api.get, self.context, 'u1')

    def test_reset_without_email(self):
        user = util.mock_user('u1', 'regular_user')
        user.email = None
        self.session.add(user)
        self.assertRaises(errors.ValidationError, self.api.get, self.context, 'u1')

    def test_reset_sending_email(self):
        user = util.mock_user('u1', 'regular_user')
        user.email = 'user@example.com'
        self.session.add(user)
        for getter in ['u1', 'user@example.com']:
            mailer.send_mail = mock.MagicMock()
            self.assertEqual({}, self.api.get(self.context, getter))
            mailer.send_mail.assert_called_once_with(
                'noreply@Test instance',
                'user@example.com',
                'Password reset for Test instance',
                'You (or someone else) requested to reset your password ' +
                'on Test instance.\nIf you wish to proceed, click this l' +
                'ink: http://example.com/password-reset/u1:4ac0be176fb36' +
                '4f13ee6b634c43220e2\nOtherwise, please ignore this email.')

    def test_confirmation_non_existing(self):
        self.assertRaises(errors.NotFoundError, self.api.post, self.context, 'u1')

    def test_confirmation_no_token(self):
        user = util.mock_user('u1', 'regular_user')
        user.email = 'user@example.com'
        self.session.add(user)
        self.context.request = {}
        self.assertRaises(errors.ValidationError, self.api.post, self.context, 'u1')

    def test_confirmation_bad_token(self):
        user = util.mock_user('u1', 'regular_user')
        user.email = 'user@example.com'
        self.session.add(user)
        self.context.request = {'token': 'bad'}
        self.assertRaises(errors.ValidationError, self.api.post, self.context, 'u1')

    def test_confirmation_good_token(self):
        user = util.mock_user('u1', 'regular_user')
        user.email = 'user@example.com'
        old_hash = user.password_hash
        self.session.add(user)
        self.context.request = {'token': '4ac0be176fb364f13ee6b634c43220e2'}
        result = self.api.post(self.context, 'u1')
        self.assertNotEqual(user.password_hash, old_hash)
        self.assertTrue(auth.is_valid_password(user, result['password']))
