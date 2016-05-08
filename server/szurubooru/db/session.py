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
    _engine = sqlalchemy.create_engine(
        '{schema}://{user}:{password}@{host}:{port}/{name}'.format(
            schema=config.config['database']['schema'],
            user=config.config['database']['user'],
            password=config.config['database']['pass'],
            host=config.config['database']['host'],
            port=config.config['database']['port'],
            name=config.config['database']['name']))
    sqlalchemy.event.listen(
        _engine, 'after_execute', lambda *args: QueryCounter.bump())
    _session_maker = sqlalchemy.orm.sessionmaker(bind=_engine)
    return sqlalchemy.orm.scoped_session(_session_maker)

# pylint: disable=invalid-name
session = create_session()
reset_query_count = QueryCounter.reset
get_query_count = QueryCounter.get
