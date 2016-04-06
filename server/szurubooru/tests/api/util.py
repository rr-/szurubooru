from datetime import datetime
from szurubooru import config, db
from szurubooru.util import misc

def mock_config(config_mock):
    config.config = config_mock

def mock_user(name, rank='admin'):
    user = db.User()
    user.name = name
    user.password = 'dummy'
    user.password_salt = 'dummy'
    user.password_hash = 'dummy'
    user.email = 'dummy'
    user.rank = rank
    user.creation_time = datetime(1997, 1, 1)
    user.avatar_style = db.User.AVATAR_GRAVATAR
    return user

def mock_context(parent):
    context = misc.dotdict()
    context.session = parent.session
    context.request = {}
    context.user = db.User()
    parent.context = context

def mock_params(context, params):
    def get_param_as_string(key, default=None):
        if key not in params:
            return default
        return params[key]
    def get_param_as_int(key, default=None):
        if key not in params:
            return default
        return int(params[key])
    context.get_param_as_string = get_param_as_string
    context.get_param_as_int = get_param_as_int
