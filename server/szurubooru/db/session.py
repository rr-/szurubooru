import threading
import sqlalchemy
from szurubooru import config


# pylint: disable=invalid-name
_engine = sqlalchemy.create_engine(config.config['database'])
sessionmaker = sqlalchemy.orm.sessionmaker(bind=_engine)
session = sqlalchemy.orm.scoped_session(sessionmaker)

_data = threading.local()


def reset_query_count():
    _data.query_count = 0


def get_query_count():
    return _data.query_count


def _bump_query_count():
    _data.query_count = getattr(_data, 'query_count', 0) + 1


sqlalchemy.event.listen(
    _engine, 'after_execute', lambda *args: _bump_query_count())
