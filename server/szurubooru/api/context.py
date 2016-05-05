import falcon
from szurubooru import errors
from szurubooru.func import net

class Context(object):
    def __init__(self):
        self.session = None
        self.user = None
        self.files = {}
        self.input = {}
        self.output = None

    def has_param(self, name):
        return name in self.input

    def has_file(self, name):
        return name in self.files or name + 'Url' in self.input

    def get_file(self, name, required=False):
        if name in self.files:
            return self.files[name]
        if name + 'Url' in self.input:
            return net.download(self.input[name + 'Url'])
        if not required:
            return None
        raise errors.MissingRequiredFileError(
            'Required file %r is missing.' % name)

    def get_param_as_list(self, name, required=False, default=None):
        if name in self.input:
            param = self.input[name]
            if not isinstance(param, list):
                return [param]
            return param
        if not required:
            return default
        raise errors.MissingRequiredParameterError(
            'Required paramter %r is missing.' % name)

    def get_param_as_string(self, name, required=False, default=None):
        if name in self.input:
            param = self.input[name]
            if isinstance(param, list):
                try:
                    param = ','.join(param)
                except:
                    raise errors.InvalidParameterError(
                        'Parameter %r is invalid - expected simple string.'
                        % name)
            return param
        if not required:
            return default
        raise errors.MissingRequiredParameterError(
            'Required paramter %r is missing.' % name)

    # pylint: disable=redefined-builtin,too-many-arguments
    def get_param_as_int(
            self, name, required=False, min=None, max=None, default=None):
        if name in self.input:
            val = self.input[name]
            try:
                val = int(val)
            except (ValueError, TypeError):
                raise errors.InvalidParameterError(
                    'Parameter %r is invalid: the value must be an integer.'
                    % name)
            if min is not None and val < min:
                raise errors.InvalidParameterError(
                    'Parameter %r is invalid: the value must be at least %r.'
                    % (name, min))
            if max is not None and val > max:
                raise errors.InvalidParameterError(
                    'Parameter %r is invalid: the value may not exceed %r.'
                    % (name, max))
            return val
        if not required:
            return default
        raise errors.MissingRequiredParameterError(
            'Required parameter %r is missing.' % name)

class Request(falcon.Request):
    context_type = Context
