import os
from szurubooru import config

def _get_full_path(path):
    return os.path.join(config.config['data_dir'], path)

def delete(path):
    full_path = _get_full_path(path)
    if os.path.exists(full_path):
        os.unlink(full_path)

def get(path):
    full_path = _get_full_path(path)
    if not os.path.exists(full_path):
        return None
    with open(full_path, 'rb') as handle:
        return handle.read()

def save(path, content):
    full_path = _get_full_path(path)
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    with open(full_path, 'wb') as handle:
        handle.write(content)
