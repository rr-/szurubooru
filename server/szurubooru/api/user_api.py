import hashlib
from szurubooru import config, search
from szurubooru.util import auth, users
from szurubooru.api.base_api import BaseApi

def _serialize_user(authenticated_user, user):
    ret = {
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
        ret['avatarUrl'] = 'http://gravatar.com/avatar/%s?d=retro&s=%d' % (
            digest, config.config['thumbnails']['avatar_width'])
    else:
        ret['avatarUrl'] = '%s/avatars/%s.jpg' % (
            config.config['data_url'].rstrip('/'), user.name.lower())

    if authenticated_user.user_id == user.user_id:
        ret['email'] = user.email

    return ret

class UserListApi(BaseApi):
    def __init__(self):
        super().__init__()
        self._search_executor = search.SearchExecutor(search.UserSearchConfig())

    def get(self, ctx):
        auth.verify_privilege(ctx.user, 'users:list')
        query = ctx.get_param_as_string('query')
        page = ctx.get_param_as_int('page', default=1, min=1)
        page_size = ctx.get_param_as_int(
            'pageSize', default=100, min=1, max=100)
        count, user_list = self._search_executor.execute(query, page, page_size)
        return {
            'query': query,
            'page': page,
            'pageSize': page_size,
            'total': count,
            'users': [_serialize_user(ctx.user, user) for user in user_list],
        }

    def post(self, ctx):
        auth.verify_privilege(ctx.user, 'users:create')

        name = ctx.get_param_as_string('name', required=True)
        password = ctx.get_param_as_string('password', required=True)
        email = ctx.get_param_as_string('email', required=False, default='')

        user = users.create_user(name, password, email, ctx.user)

        if ctx.has_param('rank'):
            users.update_rank(user, ctx.get_param_as_string('rank'), ctx.user)

        if ctx.has_param('avatarStyle'):
            users.update_avatar(
                user,
                ctx.get_param_as_string('avatarStyle'),
                ctx.get_file('avatar'))

        ctx.session.add(user)
        ctx.session.commit()
        return {'user': _serialize_user(ctx.user, user)}

class UserDetailApi(BaseApi):
    def get(self, ctx, user_name):
        auth.verify_privilege(ctx.user, 'users:view')
        user = users.get_user_by_name(user_name)
        if not user:
            raise users.UserNotFoundError('User %r not found.' % user_name)
        return {'user': _serialize_user(ctx.user, user)}

    def put(self, ctx, user_name):
        user = users.get_user_by_name(user_name)
        if not user:
            raise users.UserNotFoundError('User %r not found.' % user_name)

        if ctx.user.user_id == user.user_id:
            infix = 'self'
        else:
            infix = 'any'

        if ctx.has_param('name'):
            auth.verify_privilege(ctx.user, 'users:edit:%s:name' % infix)
            users.update_name(user, ctx.get_param_as_string('name'), ctx.user)

        if ctx.has_param('password'):
            auth.verify_privilege(ctx.user, 'users:edit:%s:pass' % infix)
            users.update_password(user, ctx.get_param_as_string('password'))

        if ctx.has_param('email'):
            auth.verify_privilege(ctx.user, 'users:edit:%s:email' % infix)
            users.update_email(user, ctx.get_param_as_string('email'))

        if ctx.has_param('rank'):
            auth.verify_privilege(ctx.user, 'users:edit:%s:rank' % infix)
            users.update_rank(user, ctx.get_param_as_string('rank'), ctx.user)

        if ctx.has_param('avatarStyle'):
            auth.verify_privilege(ctx.user, 'users:edit:%s:avatar' % infix)
            users.update_avatar(
                user,
                ctx.get_param_as_string('avatarStyle'),
                ctx.get_file('avatar'))

        ctx.session.commit()
        return {'user': _serialize_user(ctx.user, user)}

    def delete(self, ctx, user_name):
        user = users.get_user_by_name(user_name)
        if not user:
            raise users.UserNotFoundError('User %r not found.' % user_name)

        if ctx.user.user_id == user.user_id:
            infix = 'self'
        else:
            infix = 'any'

        auth.verify_privilege(ctx.user, 'users:delete:%s' % infix)
        ctx.session.delete(user)
        ctx.session.commit()
        return {}
