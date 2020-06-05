from typing import Any, Dict, List


def get_list_diff(old: List[Any], new: List[Any]) -> Any:
    equal = True
    removed = []  # type: List[Any]
    added = []  # type: List[Any]

    for item in old:
        if item not in new:
            equal = False
            removed.append(item)

    for item in new:
        if item not in old:
            equal = False
            added.append(item)

    return (
        None
        if equal
        else {"type": "list change", "added": added, "removed": removed}
    )


def get_dict_diff(old: Dict[str, Any], new: Dict[str, Any]) -> Any:
    value = {}
    equal = True

    for key in old.keys():
        if key in new:
            if old[key] != new[key]:
                if isinstance(old[key], dict) and isinstance(new[key], dict):
                    value_diff = get_dict_diff(old[key], new[key])
                    if value_diff:
                        equal = False
                        value[key] = value_diff
                elif isinstance(old[key], list) and isinstance(new[key], list):
                    value_diff = get_list_diff(old[key], new[key])
                    if value_diff:
                        equal = False
                        value[key] = value_diff
                else:
                    equal = False
                    value[key] = {
                        "type": "primitive change",
                        "old-value": old[key],
                        "new-value": new[key],
                    }
        else:
            equal = False
            value[key] = {"type": "deleted property", "value": old[key]}

    for key in new.keys():
        if key not in old:
            equal = False
            value[key] = {
                "type": "added property",
                "value": new[key],
            }

    return None if equal else {"type": "object change", "value": value}
