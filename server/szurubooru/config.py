from typing import Dict
import os
import yaml
from szurubooru import errors


def _merge(left: Dict, right: Dict) -> Dict:
    for key in right:
        if key in left:
            if isinstance(left[key], dict) and isinstance(right[key], dict):
                _merge(left[key], right[key])
            elif left[key] != right[key]:
                left[key] = right[key]
        else:
            left[key] = right[key]
    return left


def _docker_config() -> Dict:
    for key in [
            'POSTGRES_USER',
            'POSTGRES_PASSWORD',
            'POSTGRES_HOST',
            'ESEARCH_HOST'
    ]:
        if not os.getenv(key, False):
            raise errors.ConfigError(f'Environment variable "{key}" not set')
    return {
        'debug': True,
        'show_sql': int(os.getenv('LOG_SQL', 0)),
        'data_url': os.getenv('DATA_URL', 'data/'),
        'data_dir': '/data/',
        'database': 'postgres://%(user)s:%(pass)s@%(host)s:%(port)d/%(db)s' % {
            'user': os.getenv('POSTGRES_USER'),
            'pass': os.getenv('POSTGRES_PASSWORD'),
            'host': os.getenv('POSTGRES_HOST'),
            'port': int(os.getenv('POSTGRES_PORT', 5432)),
            'db': os.getenv('POSTGRES_DB', os.getenv('POSTGRES_USER'))
        },
        'elasticsearch': {
            'host': os.getenv('ESEARCH_HOST'),
            'port': int(os.getenv('ESEARCH_PORT', 9200)),
            'index': os.getenv('ESEARCH_INDEX', 'szurubooru'),
            'user': os.getenv('ESEARCH_USER', os.getenv('ESEARCH_INDEX', 'szurubooru')),
            'pass': os.getenv('ESEARCH_PASSWORD', False)
        }
    }


def _file_config(filename: str) -> Dict:
    with open(filename) as handle:
        return yaml.load(handle.read(), Loader=yaml.SafeLoader)


def _read_config() -> Dict:
    ret = _file_config('config.yaml.dist')
    if os.path.exists('config.yaml'):
        ret = _merge(ret, _file_config('config.yaml'))
    if os.path.exists('/.dockerenv'):
        ret = _merge(ret, _docker_config())
    return ret


config = _read_config()  # pylint: disable=invalid-name
