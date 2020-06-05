import hashlib
import os
import re
import tempfile
from contextlib import contextmanager
from datetime import datetime, timedelta
from typing import Any, Dict, Generator, List, Optional, Tuple, TypeVar, Union

from szurubooru import errors

T = TypeVar("T")


def snake_case_to_lower_camel_case(text: str) -> str:
    components = text.split("_")
    return components[0].lower() + "".join(
        word[0].upper() + word[1:].lower() for word in components[1:]
    )


def snake_case_to_upper_train_case(text: str) -> str:
    return "-".join(
        word[0].upper() + word[1:].lower() for word in text.split("_")
    )


def snake_case_to_lower_camel_case_keys(
    source: Dict[str, Any]
) -> Dict[str, Any]:
    target = {}
    for key, value in source.items():
        target[snake_case_to_lower_camel_case(key)] = value
    return target


@contextmanager
def create_temp_file(**kwargs: Any) -> Generator:
    (descriptor, path) = tempfile.mkstemp(**kwargs)
    os.close(descriptor)
    try:
        with open(path, "r+b") as handle:
            yield handle
    finally:
        os.remove(path)


@contextmanager
def create_temp_file_path(**kwargs: Any) -> Generator:
    (descriptor, path) = tempfile.mkstemp(**kwargs)
    os.close(descriptor)
    try:
        yield path
    finally:
        os.remove(path)


def unalias_dict(source: List[Tuple[List[str], T]]) -> Dict[str, T]:
    output_dict = {}  # type: Dict[str, T]
    for aliases, value in source:
        for alias in aliases:
            output_dict[alias] = value
    return output_dict


def get_md5(source: Union[str, bytes]) -> str:
    if not isinstance(source, bytes):
        source = source.encode("utf-8")
    md5 = hashlib.md5()
    md5.update(source)
    return md5.hexdigest()


def get_sha1(source: Union[str, bytes]) -> str:
    if not isinstance(source, bytes):
        source = source.encode("utf-8")
    sha1 = hashlib.sha1()
    sha1.update(source)
    return sha1.hexdigest()


def flip(source: Dict[Any, Any]) -> Dict[Any, Any]:
    return {v: k for k, v in source.items()}


def is_valid_email(email: Optional[str]) -> bool:
    """ Return whether given email address is valid or empty. """
    return not email or re.match(r"^[^@]*@[^@]*\.[^@]*$", email) is not None


class dotdict(dict):
    """ dot.notation access to dictionary attributes. """

    def __getattr__(self, attr: str) -> Any:
        return self.get(attr)

    __setattr__ = dict.__setitem__
    __delattr__ = dict.__delitem__


def parse_time_range(value: str) -> Tuple[datetime, datetime]:
    """ Return tuple containing min/max time for given text representation. """
    one_day = timedelta(days=1)
    one_second = timedelta(seconds=1)
    almost_one_day = one_day - one_second

    value = value.lower()
    if not value:
        raise errors.ValidationError("Empty date format.")

    if value == "today":
        now = datetime.utcnow()
        return (
            datetime(now.year, now.month, now.day, 0, 0, 0),
            datetime(now.year, now.month, now.day, 0, 0, 0) + almost_one_day,
        )

    if value == "yesterday":
        now = datetime.utcnow()
        return (
            datetime(now.year, now.month, now.day, 0, 0, 0) - one_day,
            datetime(now.year, now.month, now.day, 0, 0, 0) - one_second,
        )

    match = re.match(r"^(\d{4})$", value)
    if match:
        year = int(match.group(1))
        return (datetime(year, 1, 1), datetime(year + 1, 1, 1) - one_second)

    match = re.match(r"^(\d{4})-(\d{1,2})$", value)
    if match:
        year = int(match.group(1))
        month = int(match.group(2))
        return (
            datetime(year, month, 1),
            datetime(year, month + 1, 1) - one_second,
        )

    match = re.match(r"^(\d{4})-(\d{1,2})-(\d{1,2})$", value)
    if match:
        year = int(match.group(1))
        month = int(match.group(2))
        day = int(match.group(3))
        return (
            datetime(year, month, day),
            datetime(year, month, day + 1) - one_second,
        )

    raise errors.ValidationError("Invalid date format: %r." % value)


def icase_unique(source: List[str]) -> List[str]:
    target = []  # type: List[str]
    target_low = []  # type: List[str]
    for source_item in source:
        if source_item.lower() not in target_low:
            target.append(source_item)
            target_low.append(source_item.lower())
    return target


def value_exceeds_column_size(value: Optional[str], column: Any) -> bool:
    if not value:
        return False
    max_length = column.property.columns[0].type.length
    if max_length is None:
        return False
    return len(value) > max_length


def get_column_size(column: Any) -> Optional[int]:
    if not column:
        return None
    return column.property.columns[0].type.length


def chunks(source_list: List[Any], part_size: int) -> Generator:
    for i in range(0, len(source_list), part_size):
        yield source_list[i : i + part_size]
