''' Users public API. '''

import re
import falcon
from szurubooru.services.errors import IntegrityError

def _serialize_user(user):
    return {
        'id': user.user_id,
        'name': user.name,
        'email': user.email, # TODO: secure this
        'accessRank': user.access_rank,
        'creationTime': user.creation_time,
        'lastLoginTime': user.last_login_time,
        'avatarStyle': user.avatar_style
    }

class UserListApi(object):
    ''' API for lists of users. '''
    def __init__(self, config, auth_service, user_service):
        self._config = config
        self._auth_service = auth_service
        self._user_service = user_service

    def on_get(self, request, response):
        ''' Retrieves a list of users. '''
        self._auth_service.verify_privilege(request.context.user, 'users:list')
        request.context.result = {'message': 'Searching for users'}

    def on_post(self, request, response):
        ''' Creates a new user. '''
        self._auth_service.verify_privilege(request.context.user, 'users:create')
        name_regex = self._config['service']['user_name_regex']
        password_regex = self._config['service']['password_regex']

        try:
            name = request.context.request['name']
            password = request.context.request['password']
            email = request.context.request['email'].strip()
            if not email:
                email = None
        except KeyError as ex:
            raise falcon.HTTPBadRequest(
                'Malformed data', 'Field %r not found' % ex.args[0])

        if not re.match(name_regex, name):
            raise falcon.HTTPBadRequest(
                'Malformed data',
                'Name must validate %r expression' % name_regex)

        if not re.match(password_regex, password):
            raise falcon.HTTPBadRequest(
                'Malformed data',
                'Password must validate %r expression' % password_regex)

        session = request.context.session
        try:
            user = self._user_service.create_user(session, name, password, email)
            session.commit()
        except:
            raise IntegrityError('User %r already exists.' % name)
        request.context.result = {'user': _serialize_user(user)}

class UserDetailApi(object):
    ''' API for individual users. '''
    def __init__(self, config, auth_service, user_service):
        self._config = config
        self._auth_service = auth_service
        self._user_service = user_service

    def on_get(self, request, response, user_name):
        ''' Retrieves an user. '''
        self._auth_service.verify_privilege(request.context.user, 'users:view')
        session = request.context.session
        user = self._user_service.get_by_name(session, user_name)
        request.context.result = {'user': _serialize_user(user)}

    def on_put(self, request, response, user_name):
        ''' Updates an existing user. '''
        self._auth_service.verify_privilege(request.context.user, 'users:edit')
        request.context.result = {'message': 'Updating user ' + user_name}
