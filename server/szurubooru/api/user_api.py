''' Exports UserListApi and UserDetailApi. '''

import re
import sqlalchemy
from szurubooru.api.base_api import BaseApi
from szurubooru.errors import IntegrityError, ValidationError, NotFoundError, AuthError
from szurubooru.services.search import UserSearchConfig, SearchExecutor
from szurubooru.util import is_valid_email

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
        self._search_executor = SearchExecutor(UserSearchConfig())

    def get(self, request, context):
        ''' Retrieves a list of users. '''
        self._auth_service.verify_privilege(context.user, 'users:list')
        query = request.get_param_as_string('query')
        page = request.get_param_as_int('page', 1)
        count, users = self._search_executor.execute(context.session, query, page)
        return {
            'query': query,
            'page': page,
            'page_size': self._search_executor.page_size,
            'total': count,
            'users': [_serialize_user(context.user, user) for user in users],
        }

    def post(self, request, context):
        ''' Creates a new user. '''
        self._auth_service.verify_privilege(context.user, 'users:create')

        try:
            name = context.request['name'].strip()
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
    def __init__(self, config, auth_service, password_service, user_service):
        super().__init__()
        self._available_access_ranks = config['service']['user_ranks']
        self._name_regex = config['service']['user_name_regex']
        self._password_regex = config['service']['password_regex']
        self._password_service = password_service
        self._auth_service = auth_service
        self._user_service = user_service

    def get(self, request, context, user_name):
        ''' Retrieves an user. '''
        self._auth_service.verify_privilege(context.user, 'users:view')
        user = self._user_service.get_by_name(context.session, user_name)
        if not user:
            raise NotFoundError('User %r not found.' % user_name)
        return {'user': _serialize_user(context.user, user)}

    def put(self, request, context, user_name):
        ''' Updates an existing user. '''
        user = self._user_service.get_by_name(context.session, user_name)
        if not user:
            raise NotFoundError('User %r not found.' % user_name)

        if context.user.user_id == user.user_id:
            infix = 'self'
        else:
            infix = 'any'

        if 'name' in context.request:
            self._auth_service.verify_privilege(
                context.user, 'users:edit:%s:name' % infix)
            name = context.request['name'].strip()
            if not re.match(self._name_regex, name):
                raise ValidationError(
                    'Name must satisfy regex %r.' % self._name_regex)
            user.name = name

        if 'password' in context.request:
            password = context.request['password']
            self._auth_service.verify_privilege(
                context.user, 'users:edit:%s:pass' % infix)
            if not re.match(self._password_regex, password):
                raise ValidationError(
                    'Password must satisfy regex %r.' % self._password_regex)
            user.password_salt = self._password_service.create_password()
            user.password_hash = self._password_service.get_password_hash(
                user.password_salt, password)

        if 'email' in context.request:
            self._auth_service.verify_privilege(
                context.user, 'users:edit:%s:email' % infix)
            email = context.request['email'].strip()
            if not is_valid_email(email):
                raise ValidationError('%r is not a vaild email address.' % email)
            # prefer nulls to empty strings in the DB
            if not email:
                email = None
            user.email = email

        if 'accessRank' in context.request:
            self._auth_service.verify_privilege(
                context.user, 'users:edit:%s:rank' % infix)
            rank = context.request['accessRank'].strip()
            if not rank in self._available_access_ranks:
                raise ValidationError(
                    'Bad access rank. Valid access ranks: %r' \
                        % self._available_access_ranks)
            if self._available_access_ranks.index(context.user.access_rank) \
                    < self._available_access_ranks.index(rank):
                raise AuthError(
                    'Trying to set higher access rank than one has')
            user.access_rank = rank

        # TODO: avatar

        try:
            context.session.commit()
        except sqlalchemy.exc.IntegrityError:
            raise IntegrityError('User %r already exists.' % name)

        return {'user': _serialize_user(context.user, user)}
