''' Users public API. '''

import sqlalchemy
from szurubooru.services.errors import IntegrityError, ValidationError
from szurubooru.api.base_api import BaseApi

def _serialize_user(authenticated_user, user):
    ret = {
        'id': user.user_id,
        'name': user.name,
        'accessRank': user.access_rank,
        'creationTime': user.creation_time,
        'lastLoginTime': user.last_login_time,
        'avatarStyle': user.avatar_style
    }
    if authenticated_user.user_id == user.user_id:
        ret['email'] = user.email
    return ret

class UserListApi(BaseApi):
    ''' API for lists of users. '''
    def __init__(self, auth_service, user_service):
        super().__init__()
        self._auth_service = auth_service
        self._user_service = user_service

    def get(self, context):
        ''' Retrieves a list of users. '''
        self._auth_service.verify_privilege(context.user, 'users:list')
        return {'message': 'Searching for users'}

    def post(self, context):
        ''' Creates a new user. '''
        self._auth_service.verify_privilege(context.user, 'users:create')

        try:
            name = context.request['name']
            password = context.request['password']
            email = context.request['email'].strip()
        except KeyError as ex:
            raise ValidationError('Field %r not found.' % ex.args[0])

        user = self._user_service.create_user(
            context.session, name, password, email)
        try:
            context.session.commit()
        except sqlalchemy.exc.IntegrityError:
            raise IntegrityError('User %r already exists.' % name)
        return {'user': _serialize_user(context.user, user)}

class UserDetailApi(BaseApi):
    ''' API for individual users. '''
    def __init__(self, auth_service, user_service):
        super().__init__()
        self._auth_service = auth_service
        self._user_service = user_service

    def get(self, context, user_name):
        ''' Retrieves an user. '''
        self._auth_service.verify_privilege(context.user, 'users:view')
        user = self._user_service.get_by_name(context.session, user_name)
        return {'user': _serialize_user(context.user, user)}

    def put(self, context, user_name):
        ''' Updates an existing user. '''
        self._auth_service.verify_privilege(context.user, 'users:edit')
        return {'message': 'Updating user ' + user_name}
