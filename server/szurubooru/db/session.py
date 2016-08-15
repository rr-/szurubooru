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


def create_session():
    _engine = sqlalchemy.create_engine(config.config['database'])
    sqlalchemy.event.listen(
        _engine, 'after_execute', lambda *args: QueryCounter.bump())
    _session_maker = sqlalchemy.orm.sessionmaker(bind=_engine)
    return sqlalchemy.orm.scoped_session(_session_maker)


# pylint: disable=invalid-name
session = create_session()
reset_query_count = QueryCounter.reset
get_query_count = QueryCounter.get
