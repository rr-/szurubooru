import pytest
from datetime import datetime
from szurubooru import errors
from szurubooru.util import misc

dt = datetime

class FakeDatetime(datetime):
    @staticmethod
    def now(tz=None):
        return datetime(1997, 1, 2, 3, 4, 5, tzinfo=tz)

def test_parsing_empty_date_time():
    with pytest.raises(errors.ValidationError):
        misc.parse_time_range('')

@pytest.mark.parametrize('input,output', [
    ('today',       (dt(1997, 1, 2, 0, 0, 0), dt(1997, 1, 2, 23, 59, 59))),
    ('yesterday',   (dt(1997, 1, 1, 0, 0, 0), dt(1997, 1, 1, 23, 59, 59))),
    ('1999',        (dt(1999, 1, 1, 0, 0, 0), dt(1999, 12, 31, 23, 59, 59))),
    ('1999-2',      (dt(1999, 2, 1, 0, 0, 0), dt(1999, 2, 28, 23, 59, 59))),
    ('1999-02',     (dt(1999, 2, 1, 0, 0, 0), dt(1999, 2, 28, 23, 59, 59))),
    ('1999-2-6',    (dt(1999, 2, 6, 0, 0, 0), dt(1999, 2, 6, 23, 59, 59))),
    ('1999-02-6',   (dt(1999, 2, 6, 0, 0, 0), dt(1999, 2, 6, 23, 59, 59))),
    ('1999-2-06',   (dt(1999, 2, 6, 0, 0, 0), dt(1999, 2, 6, 23, 59, 59))),
    ('1999-02-06',  (dt(1999, 2, 6, 0, 0, 0), dt(1999, 2, 6, 23, 59, 59))),
])
def test_parsing_date_time(input, output):
    misc.datetime.datetime = FakeDatetime
    assert misc.parse_time_range(input) == output
