import sqlalchemy
from szurubooru import config


class QueryCounter(object):
    _query_count = 0

    @staticmethod
    def bump():
        QueryCounter._query_count += 1

    @staticmethod
    def reset():
        QueryCounter._query_count = 0

    @staticmethod
    def get():
        return QueryCounter._query_count


# pylint: disable=invalid-name
_engine = sqlalchemy.create_engine(config.config['database'])
sessionmaker = sqlalchemy.orm.sessionmaker(bind=_engine)
session = sqlalchemy.orm.scoped_session(sessionmaker)
reset_query_count = QueryCounter.reset
get_query_count = QueryCounter.get

sqlalchemy.event.listen(
    _engine, 'after_execute', lambda *args: QueryCounter.bump())
