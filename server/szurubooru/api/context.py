import falcon
from szurubooru import errors

class Context(object):
    def __init__(self):
        self.session = None
        self.user = None
        self.files = {}
        self.input = {}
        self.output = None

    def has_param(self, name):
        return name in self.input

    def get_file(self, name):
        return self.files.get(name, None)

    def get_param_as_list(self, name, required=False, default=None):
        if name in self.input:
            param = self.input[name]
            if not isinstance(param, list):
                return [param]
            return param
        if not required:
            return default
        raise errors.ValidationError('Required paramter %r is missing.' % name)

    def get_param_as_string(self, name, required=False, default=None):
        if name in self.input:
            param = self.input[name]
            if isinstance(param, list):
                param = ','.join(param)
            return param
        if not required:
            return default
        raise errors.ValidationError('Required paramter %r is missing.' % name)

    def get_param_as_int(
            self, name, required=False, min=None, max=None, default=None):
        if name in self.input:
            val = self.input[name]
            try:
                val = int(val)
            except (ValueError, TypeError):
                raise errors.ValidationError(
                    'Parameter %r is invalid: the value must be an integer.'
                    % name)

            if min is not None and val < min:
                raise errors.ValidationError(
                    'Parameter %r is invalid: the value must be at least %r.'
                    % (name, min))

            if max is not None and val > max:
                raise errors.ValidationError(
                    'Parameter %r is invalid: the value may not exceed %r.'
                    % (name, max))

            return val

        if not required:
            return default
        raise errors.ValidationError(
            'Required parameter %r is missing.' % name)

class Request(falcon.Request):
    context_type = Context
