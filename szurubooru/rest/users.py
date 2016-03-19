class UserList(object):
    def __init__(self, auth_service):
        self._auth_service = auth_service

    def on_get(self, request, response):
        self._auth_service.verify_privilege(request.context['user'], 'users:list')
        request.context['reuslt'] = {'message': 'Searching for users'}

    def on_post(self, request, response):
        self._auth_service.verify_privilege(request.context['user'], 'users:create')
        request.context['result'] = {'message': 'Creating user'}

class User(object):
    def __init__(self, auth_service):
        self._auth_service = auth_service

    def on_get(self, request, response, user_id):
        self._auth_service.verify_privilege(request.context['user'], 'users:view')
        request.context['result'] = {'message': 'Getting user ' + user_id}

    def on_put(self, request, response, user_id):
        self._auth_service.verify_privilege(request.context['user'], 'users:edit')
        request.context['result'] = {'message': 'Updating user ' + user_id}
