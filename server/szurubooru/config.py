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

def read_config():
    with open('../config.yaml.dist') as handle:
        ret = yaml.load(handle.read())
        if os.path.exists('../config.yaml'):
            with open('../config.yaml') as handle:
                ret = merge(ret, yaml.load(handle.read()))
        return ret

def validate_config(src):
    '''
    Check whether config doesn't contain errors that might prove
    lethal at runtime.
    '''
    from szurubooru.db.user import User
    for privilege, rank in src['privileges'].items():
        if rank not in User.ALL_RANKS:
            raise errors.ConfigError(
                'Rank %r for privilege %r is missing' % (rank, privilege))
    if src['default_rank'] not in User.ALL_RANKS:
        raise errors.ConfigError(
            'Default rank %r is not on the list of known ranks' % (
                src['default_rank']))

    for key in ['base_url', 'api_url', 'data_url', 'data_dir']:
        if not src[key]:
            raise errors.ConfigError(
                'Service is not configured: %r is missing' % key)

    if not os.path.isabs(src['data_dir']):
        raise errors.ConfigError(
            'data_dir must be an absolute path')

    for key in ['schema', 'host', 'port', 'user', 'pass', 'name']:
        if not src['database'][key]:
            raise errors.ConfigError(
                'Database is not configured: %r is missing' % key)

config = read_config() # pylint: disable=invalid-name
validate_config(config)
