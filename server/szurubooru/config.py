import logging
import os
from typing import Dict

import yaml

from szurubooru import errors

logger = logging.getLogger(__name__)


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
    if "TEST_ENVIRONMENT" not in os.environ:
        for key in ["POSTGRES_USER", "POSTGRES_PASSWORD", "POSTGRES_HOST"]:
            if key not in os.environ:
                raise errors.ConfigError(
                    f'Environment variable "{key}" not set'
                )
    return {
        "debug": True,
        "show_sql": int(os.getenv("LOG_SQL", 0)),
        "data_url": os.getenv("DATA_URL", "data/"),
        "data_dir": "/data/",
        "database": "postgres://%(user)s:%(pass)s@%(host)s:%(port)d/%(db)s"
        % {
            "user": os.getenv("POSTGRES_USER"),
            "pass": os.getenv("POSTGRES_PASSWORD"),
            "host": os.getenv("POSTGRES_HOST"),
            "port": int(os.getenv("POSTGRES_PORT", 5432)),
            "db": os.getenv("POSTGRES_DB", os.getenv("POSTGRES_USER")),
        },
    }


def _file_config(filename: str) -> Dict:
    with open(filename) as handle:
        return yaml.load(handle.read(), Loader=yaml.SafeLoader) or {}


def _read_config() -> Dict:
    ret = _file_config("config.yaml.dist")
    if os.path.isfile("config.yaml"):
        ret = _merge(ret, _file_config("config.yaml"))
    elif os.path.isdir("config.yaml"):
        logger.warning(
            "'config.yaml' should be a file, not a directory, skipping"
        )
    if os.path.exists("/.dockerenv"):
        ret = _merge(ret, _docker_config())
    return ret


config = _read_config()
