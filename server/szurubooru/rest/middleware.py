# pylint: disable=invalid-name
pre_hooks = []
post_hooks = []

def pre_hook(handler):
    pre_hooks.append(handler)

def post_hook(handler):
    post_hooks.insert(0, handler)
