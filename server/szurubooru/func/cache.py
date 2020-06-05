from datetime import datetime
from typing import Any, Dict, List


class LruCacheItem:
    def __init__(self, key: object, value: Any) -> None:
        self.key = key
        self.value = value
        self.timestamp = datetime.utcnow()


class LruCache:
    def __init__(self, length: int) -> None:
        self.length = length
        self.hash = {}  # type: Dict[object, LruCacheItem]
        self.item_list = []  # type: List[LruCacheItem]

    def insert_item(self, item: LruCacheItem) -> None:
        if item.key in self.hash:
            item_index = next(
                i for i, v in enumerate(self.item_list) if v.key == item.key
            )
            self.item_list[:] = (
                self.item_list[:item_index] + self.item_list[item_index + 1 :]
            )
            self.item_list.insert(0, item)
        else:
            if len(self.item_list) > self.length:
                self.remove_item(self.item_list[-1])
            self.hash[item.key] = item
            self.item_list.insert(0, item)

    def remove_all(self) -> None:
        self.hash = {}
        self.item_list = []

    def remove_item(self, item: LruCacheItem) -> None:
        del self.hash[item.key]
        del self.item_list[self.item_list.index(item)]


_CACHE = LruCache(length=100)


def purge() -> None:
    _CACHE.remove_all()


def has(key: object) -> bool:
    return key in _CACHE.hash


def get(key: object) -> Any:
    return _CACHE.hash[key].value


def remove(key: object) -> None:
    if has(key):
        del _CACHE.hash[key]


def put(key: object, value: Any) -> None:
    _CACHE.insert_item(LruCacheItem(key, value))
