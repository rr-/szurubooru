import os
import yaml
from szurubooru import errors

def merge(left, right):
    for key in right:
        if key in left:
            if isinstance(left[key], dict) and isinstance(right[key], dict):
                merge(left[key], right[key])
            elif left[key] != right[key]:
                left[key] = right[key]
        else:
            left[key] = right[key]
    return left

class Config(object):
    ''' Config parser and container. '''
    def __init__(self):
        with open('../config.yaml.dist') as handle:
            self.config = yaml.load(handle.read())
        if os.path.exists('../config.yaml'):
            with open('../config.yaml') as handle:
                self.config = merge(self.config, yaml.load(handle.read()))
        self._validate()

    def __getitem__(self, key):
        return self.config[key]

    def _validate(self):
        '''
        Check whether config doesn't contain errors that might prove
        lethal at runtime.
        '''
        all_ranks = self['ranks']
        for privilege, rank in self['privileges'].items():
            if rank not in all_ranks:
                raise errors.ConfigError(
                    'Rank %r for privilege %r is missing' % (rank, privilege))
        for rank in ['anonymous', 'admin', 'nobody']:
            if rank not in all_ranks:
                raise errors.ConfigError('Protected rank %r is missing' % rank)
        if self['default_rank'] not in all_ranks:
            raise errors.ConfigError(
                'Default rank %r is not on the list of known ranks' % (
                    self['default_rank']))

        for key in ['base_url', 'api_url', 'data_url', 'data_dir']:
            if not self[key]:
                raise errors.ConfigError(
                    'Service is not configured: %r is missing' % key)

        if not os.path.isabs(self['data_dir']):
            raise errors.ConfigError(
                'data_dir must be an absolute path')

        for key in ['schema', 'host', 'port', 'user', 'pass', 'name']:
            if not self['database'][key]:
                raise errors.ConfigError(
                    'Database is not configured: %r is missing' % key)

        if not len(self['tag_categories']):
            raise errors.ConfigError('Must have at least one tag category')

config = Config() # pylint: disable=invalid-name
