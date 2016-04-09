import hashlib
from szurubooru import config, errors, search
from szurubooru.util import auth, users
from szurubooru.api.base_api import BaseApi

def _serialize_user(authenticated_user, user):
    ret = {
        'id': user.user_id,
        'name': user.name,
        'rank': user.rank,
        'rankName': config.config['rank_names'].get(user.rank, 'Unknown'),
        'creationTime': user.creation_time,
        'lastLoginTime': user.last_login_time,
        'avatarStyle': user.avatar_style
    }

    if user.avatar_style == user.AVATAR_GRAVATAR:
        md5 = hashlib.md5()
        md5.update((user.email or user.name).lower().encode('utf-8'))
        digest = md5.hexdigest()
        ret['avatarUrl'] = 'http://gravatar.com/avatar/%s?s=%d' % (
            digest, config.config['thumbnails']['avatar_width'])
    else:
        ret['avatarUrl'] = '%s/avatars/%s.jpg' % (
            config.config['data_url'].rstrip('/'), user.name.lower())

    if authenticated_user.user_id == user.user_id:
        ret['email'] = user.email

    return ret

class UserListApi(BaseApi):
    ''' API for lists of users. '''

    def __init__(self):
        super().__init__()
        self._search_executor = search.SearchExecutor(search.UserSearchConfig())

    def get(self, context):
        ''' Retrieve a list of users. '''
        auth.verify_privilege(context.user, 'users:list')
        query = context.get_param_as_string('query')
        page = context.get_param_as_int('page', 1)
        count, user_list = self._search_executor.execute(context.session, query, page)
        return {
            'query': query,
            'page': page,
            'pageSize': self._search_executor.page_size,
            'total': count,
            'users': [_serialize_user(context.user, user) for user in user_list],
        }

    def post(self, context):
        ''' Create a new user. '''
        auth.verify_privilege(context.user, 'users:create')

        try:
            name = context.request['name'].strip()
            password = context.request['password']
            email = context.request['email'].strip()
        except KeyError as ex:
            raise errors.ValidationError('Field %r not found.' % ex.args[0])

        if users.get_by_name(context.session, name):
            raise errors.IntegrityError('User %r already exists.' % name)
        user = users.create_user(context.session, name, password, email)
        context.session.add(user)
        context.session.commit()
        return {'user': _serialize_user(context.user, user)}

class UserDetailApi(BaseApi):
    ''' API for individual users. '''

    def get(self, context, user_name):
        ''' Retrieve an user. '''
        auth.verify_privilege(context.user, 'users:view')
        user = users.get_by_name(context.session, user_name)
        if not user:
            raise errors.NotFoundError('User %r not found.' % user_name)
        return {'user': _serialize_user(context.user, user)}

    def put(self, context, user_name):
        ''' Update an existing user. '''
        user = users.get_by_name(context.session, user_name)
        if not user:
            raise errors.NotFoundError('User %r not found.' % user_name)

        if context.user.user_id == user.user_id:
            infix = 'self'
        else:
            infix = 'any'

        if 'name' in context.request:
            auth.verify_privilege(context.user, 'users:edit:%s:name' % infix)
            other_user = users.get_by_name(context.session, context.request['name'])
            if other_user and other_user.user_id != user.user_id:
                raise errors.IntegrityError('User %r already exists.' % user.name)
            users.update_name(user, context.request['name'])

        if 'password' in context.request:
            auth.verify_privilege(context.user, 'users:edit:%s:pass' % infix)
            users.update_password(user, context.request['password'])

        if 'email' in context.request:
            auth.verify_privilege(context.user, 'users:edit:%s:email' % infix)
            users.update_email(user, context.request['email'])

        if 'rank' in context.request:
            auth.verify_privilege(context.user, 'users:edit:%s:rank' % infix)
            users.update_rank(user, context.request['rank'], context.user)

        if 'avatar_style' in context.request:
            auth.verify_privilege(context.user, 'users:edit:%s:avatar' % infix)
            users.update_avatar(
                user,
                context.request['avatar_style'],
                context.files.get('avatar') or None)

        context.session.commit()
        return {'user': _serialize_user(context.user, user)}

    def delete(self, context, user_name):
        ''' Delete an existing user. '''
        user = users.get_by_name(context.session, user_name)
        if not user:
            raise errors.NotFoundError('User %r not found.' % user_name)

        if context.user.user_id == user.user_id:
            infix = 'self'
        else:
            infix = 'any'

        auth.verify_privilege(context.user, 'users:delete:%s' % infix)
        context.session.delete(user)
        context.session.commit()
        return {}
