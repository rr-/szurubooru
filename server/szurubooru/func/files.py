import os
import glob
from typing import Any, List, Optional

from szurubooru import config


def _get_full_path(path: str) -> str:
    return os.path.join(config.config["data_dir"], path)


def delete(path: str) -> None:
    full_path = _get_full_path(path)
    if os.path.exists(full_path):
        os.unlink(full_path)


def has(path: str) -> bool:
    return os.path.exists(_get_full_path(path))


def scan(path: str) -> List[Any]:
    if has(path):
        return list(os.scandir(_get_full_path(path)))
    return []


def find(path: str, pattern: str) -> List[Any]:
    return glob.glob(glob.escape(_get_full_path(path) + "/") + pattern)


def move(source_path: str, target_path: str) -> None:
    os.rename(_get_full_path(source_path), _get_full_path(target_path))


def get(path: str) -> Optional[bytes]:
    full_path = _get_full_path(path)
    if not os.path.exists(full_path):
        return None
    with open(full_path, "rb") as handle:
        return handle.read()


def save(path: str, content: bytes) -> None:
    full_path = _get_full_path(path)
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    with open(full_path, "wb") as handle:
        handle.write(content)
