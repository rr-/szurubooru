from typing import Any
import threading
import sqlalchemy as sa
import sqlalchemy.orm
from szurubooru import config

# pylint: disable=invalid-name
_data = threading.local()
_engine = sa.create_engine(config.config['database'])  # type: Any
_sessionmaker = sa.orm.sessionmaker(bind=_engine, autoflush=False)  # type: Any
session = sa.orm.scoped_session(_sessionmaker)  # type: Any


def get_session() -> Any:
    global session
    return session


def set_sesssion(new_session: Any) -> None:
    global session
    session = new_session


def reset_query_count() -> None:
    _data.query_count = 0


def get_query_count() -> int:
    return _data.query_count


def _bump_query_count() -> None:
    _data.query_count = getattr(_data, 'query_count', 0) + 1


sa.event.listen(_engine, 'after_execute', lambda *args: _bump_query_count())

import time
import logging
 
logger = logging.getLogger("myapp.sqltime")
logger.setLevel(logging.INFO)

def before_cursor_execute(conn, cursor, statement,
                        parameters, context, executemany):
    conn.info.setdefault('query_start_time', []).append(time.time())
    logger.info("Start Query: %s" % statement)

def after_cursor_execute(conn, cursor, statement,
                        parameters, context, executemany):
    total = time.time() - conn.info['query_start_time'].pop(-1)
    logger.info("Total Time: %f" % total)

sa.event.listen(_engine, "before_cursor_execute", before_cursor_execute)
sa.event.listen(_engine, "after_cursor_execute", after_cursor_execute)
