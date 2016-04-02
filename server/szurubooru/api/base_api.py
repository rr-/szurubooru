''' Exports BaseApi. '''

import types

def _bind_method(target, desired_method_name):
    actual_method = getattr(target, desired_method_name)
    def _wrapper_method(self, request, response, *args, **kwargs):
        request.context.result = actual_method(request.context, *args, **kwargs)
    return types.MethodType(_wrapper_method, target)

class BaseApi(object):
    '''
    A wrapper around falcon's API interface that eases context and result
    management.
    '''

    def __init__(self):
        self._translate_routes()

    def _translate_routes(self):
        for method_name in ['GET', 'PUT', 'POST', 'DELETE']:
            desired_method_name = method_name.lower()
            falcon_method_name = 'on_%s' % method_name.lower()
            if hasattr(self, desired_method_name):
                setattr(
                    self,
                    falcon_method_name,
                    _bind_method(self, desired_method_name))
