''' Various hooks that get executed for each request. '''

from szurubooru.middleware.authenticator import Authenticator
from szurubooru.middleware.context_adapter import ContextAdapter
from szurubooru.middleware.require_json import RequireJson
from szurubooru.middleware.db_session import DbSession
