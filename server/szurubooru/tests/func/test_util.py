from datetime import datetime

import pytest

from szurubooru import errors
from szurubooru.func import util

dt = datetime


def test_parsing_empty_date_time():
    with pytest.raises(errors.ValidationError):
        util.parse_time_range("")


@pytest.mark.parametrize(
    "output,input",
    [
        ((dt(1997, 1, 2, 0, 0, 0), dt(1997, 1, 2, 23, 59, 59)), "today"),
        ((dt(1997, 1, 1, 0, 0, 0), dt(1997, 1, 1, 23, 59, 59)), "yesterday"),
        ((dt(1999, 1, 1, 0, 0, 0), dt(1999, 12, 31, 23, 59, 59)), "1999"),
        ((dt(1999, 2, 1, 0, 0, 0), dt(1999, 2, 28, 23, 59, 59)), "1999-2"),
        ((dt(1999, 2, 1, 0, 0, 0), dt(1999, 2, 28, 23, 59, 59)), "1999-02"),
        ((dt(1999, 2, 6, 0, 0, 0), dt(1999, 2, 6, 23, 59, 59)), "1999-2-6"),
        ((dt(1999, 2, 6, 0, 0, 0), dt(1999, 2, 6, 23, 59, 59)), "1999-02-6"),
        ((dt(1999, 2, 6, 0, 0, 0), dt(1999, 2, 6, 23, 59, 59)), "1999-2-06"),
        ((dt(1999, 2, 6, 0, 0, 0), dt(1999, 2, 6, 23, 59, 59)), "1999-02-06"),
    ],
)
def test_parsing_date_time(fake_datetime, input, output):
    with fake_datetime("1997-01-02 03:04:05"):
        assert util.parse_time_range(input) == output


@pytest.mark.parametrize(
    "input,output",
    [
        ([], []),
        (["a", "b", "c"], ["a", "b", "c"]),
        (["a", "b", "a"], ["a", "b"]),
        (["a", "a", "b"], ["a", "b"]),
        (["a", "A", "b"], ["a", "b"]),
        (["a", "A", "b", "B"], ["a", "b"]),
    ],
)
def test_icase_unique(input, output):
    assert util.icase_unique(input) == output
