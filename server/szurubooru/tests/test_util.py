import unittest
from datetime import datetime
import szurubooru.util
from szurubooru.util import parse_time_range
from szurubooru.errors import ValidationError

class FakeDatetime(datetime):
    @staticmethod
    def now(tz=None):
        return datetime(1997, 1, 2, 3, 4, 5, tzinfo=tz)

class TestParseTime(unittest.TestCase):
    def test_empty(self):
        self.assertRaises(ValidationError, parse_time_range, '')

    def test_today(self):
        szurubooru.util.datetime.datetime = FakeDatetime
        date_min, date_max = parse_time_range('today')
        self.assertEqual(date_min, datetime(1997, 1, 2, 0, 0, 0))
        self.assertEqual(date_max, datetime(1997, 1, 2, 23, 59, 59))

    def test_yesterday(self):
        szurubooru.util.datetime.datetime = FakeDatetime
        date_min, date_max = parse_time_range('yesterday')
        self.assertEqual(date_min, datetime(1997, 1, 1, 0, 0, 0))
        self.assertEqual(date_max, datetime(1997, 1, 1, 23, 59, 59))

    def test_year(self):
        date_min, date_max = parse_time_range('1999')
        self.assertEqual(date_min, datetime(1999, 1, 1, 0, 0, 0))
        self.assertEqual(date_max, datetime(1999, 12, 31, 23, 59, 59))

    def test_month(self):
        for text in ['1999-2', '1999-02']:
            date_min, date_max = parse_time_range(text)
            self.assertEqual(date_min, datetime(1999, 2, 1, 0, 0, 0))
            self.assertEqual(date_max, datetime(1999, 2, 28, 23, 59, 59))

    def test_day(self):
        for text in ['1999-2-6', '1999-02-6', '1999-2-06', '1999-02-06']:
            date_min, date_max = parse_time_range(text)
            self.assertEqual(date_min, datetime(1999, 2, 6, 0, 0, 0))
            self.assertEqual(date_max, datetime(1999, 2, 6, 23, 59, 59))
