import pytest

from szurubooru.func import diff


@pytest.mark.parametrize(
    "old,new,expected",
    [
        (
            [],
            [],
            None,
        ),
        (
            [],
            ["added"],
            {"type": "list change", "added": ["added"], "removed": []},
        ),
        (
            ["removed"],
            [],
            {"type": "list change", "added": [], "removed": ["removed"]},
        ),
        (
            ["untouched"],
            ["untouched"],
            None,
        ),
        (
            ["untouched"],
            ["untouched", "added"],
            {"type": "list change", "added": ["added"], "removed": []},
        ),
        (
            ["untouched", "removed"],
            ["untouched"],
            {"type": "list change", "added": [], "removed": ["removed"]},
        ),
    ],
)
def test_get_list_diff(old, new, expected):
    assert diff.get_list_diff(old, new) == expected


@pytest.mark.parametrize(
    "old,new,expected",
    [
        (
            {},
            {},
            None,
        ),
        (
            {"removed key": "removed value"},
            {},
            {
                "type": "object change",
                "value": {
                    "removed key": {
                        "type": "deleted property",
                        "value": "removed value",
                    },
                },
            },
        ),
        (
            {},
            {"added key": "added value"},
            {
                "type": "object change",
                "value": {
                    "added key": {
                        "type": "added property",
                        "value": "added value",
                    },
                },
            },
        ),
        (
            {"key": "old value"},
            {"key": "new value"},
            {
                "type": "object change",
                "value": {
                    "key": {
                        "type": "primitive change",
                        "old-value": "old value",
                        "new-value": "new value",
                    },
                },
            },
        ),
        (
            {"key": "untouched"},
            {"key": "untouched"},
            None,
        ),
        (
            {"key": "untouched", "removed key": "removed value"},
            {"key": "untouched"},
            {
                "type": "object change",
                "value": {
                    "removed key": {
                        "type": "deleted property",
                        "value": "removed value",
                    },
                },
            },
        ),
        (
            {"key": "untouched"},
            {"key": "untouched", "added key": "added value"},
            {
                "type": "object change",
                "value": {
                    "added key": {
                        "type": "added property",
                        "value": "added value",
                    },
                },
            },
        ),
        (
            {"key": "untouched", "changed key": "old value"},
            {"key": "untouched", "changed key": "new value"},
            {
                "type": "object change",
                "value": {
                    "changed key": {
                        "type": "primitive change",
                        "old-value": "old value",
                        "new-value": "new value",
                    },
                },
            },
        ),
        (
            {"key": {"subkey": "old value"}},
            {"key": {"subkey": "new value"}},
            {
                "type": "object change",
                "value": {
                    "key": {
                        "type": "object change",
                        "value": {
                            "subkey": {
                                "type": "primitive change",
                                "old-value": "old value",
                                "new-value": "new value",
                            },
                        },
                    },
                },
            },
        ),
        (
            {"key": {}},
            {"key": {"subkey": "removed value"}},
            {
                "type": "object change",
                "value": {
                    "key": {
                        "type": "object change",
                        "value": {
                            "subkey": {
                                "type": "added property",
                                "value": "removed value",
                            },
                        },
                    },
                },
            },
        ),
        (
            {"key": {"subkey": "removed value"}},
            {"key": {}},
            {
                "type": "object change",
                "value": {
                    "key": {
                        "type": "object change",
                        "value": {
                            "subkey": {
                                "type": "deleted property",
                                "value": "removed value",
                            },
                        },
                    },
                },
            },
        ),
        (
            {"key": ["old value"]},
            {"key": ["new value"]},
            {
                "type": "object change",
                "value": {
                    "key": {
                        "type": "list change",
                        "added": ["new value"],
                        "removed": ["old value"],
                    },
                },
            },
        ),
        (
            {"key": []},
            {"key": ["new value"]},
            {
                "type": "object change",
                "value": {
                    "key": {
                        "type": "list change",
                        "added": ["new value"],
                        "removed": [],
                    },
                },
            },
        ),
        (
            {"key": ["removed value"]},
            {"key": []},
            {
                "type": "object change",
                "value": {
                    "key": {
                        "type": "list change",
                        "added": [],
                        "removed": ["removed value"],
                    },
                },
            },
        ),
    ],
)
def test_get_dict_diff(old, new, expected):
    assert diff.get_dict_diff(old, new) == expected
