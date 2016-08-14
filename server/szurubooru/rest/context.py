from szurubooru import errors
from szurubooru.func import net

def _lower_first(source):
    return source[0].lower() + source[1:]

def _param_wrapper(func):
    def wrapper(self, name, required=False, default=None, **kwargs):
        # pylint: disable=protected-access
        if name in self._params:
            value = self._params[name]
            try:
                value = func(self, value, **kwargs)
            except errors.InvalidParameterError as ex:
                raise errors.InvalidParameterError(
                    'Parameter %r is invalid: %s' % (
                        name, _lower_first(str(ex))))
            return value
        if not required:
            return default
        raise errors.MissingRequiredParameterError(
            'Required parameter %r is missing.' % name)
    return wrapper

class Context():
    # pylint: disable=too-many-arguments
    def __init__(self, method, url, headers=None, params=None, files=None):
        self.method = method
        self.url = url
        self._headers = headers or {}
        self._params = params or {}
        self._files = files or {}

        # provided by middleware
        # self.session = None
        # self.user = None

    def has_header(self, name):
        return name in self._headers

    def get_header(self, name):
        return self._headers.get(name, None)

    def has_file(self, name):
        return name in self._files or name + 'Url' in self._params

    def get_file(self, name, required=False):
        if name in self._files:
            return self._files[name]
        if name + 'Url' in self._params:
            return net.download(self._params[name + 'Url'])
        if not required:
            return None
        raise errors.MissingRequiredFileError(
            'Required file %r is missing.' % name)

    def has_param(self, name):
        return name in self._params

    @_param_wrapper
    def get_param_as_list(self, value):
        if not isinstance(value, list):
            if ',' in value:
                return value.split(',')
            return [value]
        return value

    @_param_wrapper
    def get_param_as_string(self, value):
        if isinstance(value, list):
            try:
                value = ','.join(value)
            except:
                raise errors.InvalidParameterError('Expected simple string.')
        return value

    # pylint: disable=redefined-builtin
    @_param_wrapper
    def get_param_as_int(self, value, min=None, max=None):
        try:
            value = int(value)
        except (ValueError, TypeError):
            raise errors.InvalidParameterError(
                'The value must be an integer.')
        if min is not None and value < min:
            raise errors.InvalidParameterError(
                'The value must be at least %r.' % min)
        if max is not None and value > max:
            raise errors.InvalidParameterError(
                'The value may not exceed %r.' % max)
        return value

    @_param_wrapper
    def get_param_as_bool(self, value):
        value = str(value).lower()
        if value in ['1', 'y', 'yes', 'yeah', 'yep', 'yup', 't', 'true']:
            return True
        if value in ['0', 'n', 'no', 'nope', 'f', 'false']:
            return False
        raise errors.InvalidParameterError('The value must be a boolean value.')
