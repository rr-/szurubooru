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


def read_config() -> Dict:
    with open('../config.yaml.dist') as handle:
        ret = yaml.load(handle.read())
        if os.path.exists('../config.yaml'):
            with open('../config.yaml') as handle:
                ret = merge(ret, yaml.load(handle.read()))
        return ret


config = read_config()  # pylint: disable=invalid-name
