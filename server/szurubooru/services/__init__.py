'''
Middle layer between REST API and database.
All the business logic goes here.
'''

from szurubooru.services.mailer import Mailer
from szurubooru.services.auth_service import AuthService
from szurubooru.services.user_service import UserService
from szurubooru.services.password_service import PasswordService
