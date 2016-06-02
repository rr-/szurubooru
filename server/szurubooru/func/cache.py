from datetime import datetime

class LruCacheItem(object):
    def __init__(self, key, value):
        self.key = key
        self.value = value
        self.timestamp = datetime.now()

class LruCache(object):
    def __init__(self, length, delta=None):
        self.length = length
        self.delta = delta
        self.hash = {}
        self.item_list = []

    def insert_item(self, item):
        if item.key in self.hash:
            item_index = next(i \
                for i, v in enumerate(self.item_list) \
                if v.key == item.key)
            self.item_list[:] \
                = self.item_list[:item_index] \
                + self.item_list[item_index+1:]
            self.item_list.insert(0, item)
        else:
            if len(self.item_list) > self.length:
                self.remove_item(self.item_list[-1])
            self.hash[item.key] = item
            self.item_list.insert(0, item)

    def remove_all(self):
        self.hash = {}
        self.item_list = []

    def remove_item(self, item):
        del self.hash[item.key]
        del self.item_list[self.item_list.index(item)]

_CACHE = LruCache(length=100)

def purge():
    _CACHE.remove_all()

def has(key):
    return key in _CACHE.hash

def get(key):
    return _CACHE.hash[key].value

def put(key, value):
    _CACHE.insert_item(LruCacheItem(key, value))
