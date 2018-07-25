from typing import Dict
import os
import yaml
from szurubooru import errors


def merge(left: Dict, right: Dict) -> Dict:
    for key in right:
        if key in left:
            if isinstance(left[key], dict) and isinstance(right[key], dict):
                merge(left[key], right[key])
            elif left[key] != right[key]:
                left[key] = right[key]
        else:
            left[key] = right[key]
    return left


def docker_config() -> Dict:
    for key in [
            'POSTGRES_USER',
            'POSTGRES_PASSWORD',
            'POSTGRES_HOST',
            'ESEARCH_HOST'
    ]:
        if not os.getenv(key, False) and os.getenv('CI') != 'true':
            raise errors.ConfigError(f'Environment variable "{key}" not set')
    return {
        'debug': True,
        'show_sql': int(os.getenv('LOG_SQL', 0)),
        'data_url': os.getenv('DATA_URL', '/data/'),
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
            'index': os.getenv('ESEARCH_INDEX', 'szurubooru')
        }
    }


def read_config() -> Dict:
    with open('../config.yaml.dist') as handle:
        ret = yaml.load(handle.read())
        if os.path.exists('../config.yaml'):
            with open('../config.yaml') as handle:
                ret = merge(ret, yaml.load(handle.read()))
        elif os.path.exists('/.dockerenv') and os.getenv('CI') != 'true':
            ret = merge(ret, docker_config())

        return ret


config = read_config()  # pylint: disable=invalid-name
