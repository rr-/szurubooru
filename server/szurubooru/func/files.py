import os
import shutil
from io import BufferedIOBase
from typing import Any, List, Optional

from szurubooru import config


def get_full_path(path: str) -> str:
    return os.path.join(config.config["data_dir"], path)


def delete(path: str) -> None:
    full_path = get_full_path(path)
    if os.path.exists(full_path):
        os.unlink(full_path)


def has(path: str) -> bool:
    return os.path.exists(get_full_path(path))


def scan(path: str) -> List[Any]:
    if has(path):
        return list(os.scandir(get_full_path(path)))
    return []


def move(source_path: str, target_path: str) -> None:
    os.rename(get_full_path(source_path), get_full_path(target_path))


def copy(source_path: str, target_path: str) -> None:
    shutil.copyfile(get_full_path(source_path), get_full_path(target_path))


def get(path: str) -> Optional[bytes]:
    full_path = get_full_path(path)
    if not os.path.exists(full_path):
        return None
    with open(full_path, "rb") as handle:
        return handle.read()


def get_handle(path: str) -> Optional[BufferedIOBase]:
    full_path = get_full_path(path)
    if not os.path.exists(full_path):
        return None
    return open(full_path, "rb")


def get_file_size(path: str) -> int:
    full_path = get_full_path(path)
    if not os.path.exists(full_path):
        return 0
    return os.path.getsize(full_path)


def save(path: str, content: bytes) -> None:
    full_path = get_full_path(path)
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    with open(full_path, "wb") as handle:
        handle.write(content)
