import os
from szurubooru import config

def save(path, content):
    full_path = os.path.join(config.config['data_dir'], path)
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    with open(full_path, 'wb') as handle:
        handle.write(content)
