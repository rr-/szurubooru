import sqlalchemy
from szurubooru import config

# pylint: disable=invalid-name
_engine = sqlalchemy.create_engine(
    '{schema}://{user}:{password}@{host}:{port}/{name}'.format(
        schema=config.config['database']['schema'],
        user=config.config['database']['user'],
        password=config.config['database']['pass'],
        host=config.config['database']['host'],
        port=config.config['database']['port'],
        name=config.config['database']['name']))
_session_maker = sqlalchemy.orm.sessionmaker(bind=_engine)
session = sqlalchemy.orm.scoped_session(_session_maker)
