import os
import sys

import alembic
import sqlalchemy
import logging.config

# make szurubooru module importable
dir_to_self = os.path.dirname(os.path.realpath(__file__))
sys.path.append(os.path.join(dir_to_self, *[os.pardir] * 2))

import szurubooru.db.base
import szurubooru.config

alembic_config = alembic.context.config
logging.config.fileConfig(alembic_config.config_file_name)

szuru_config = szurubooru.config.config
alembic_config.set_main_option(
    'sqlalchemy.url',
    '{schema}://{user}:{password}@{host}:{port}/{name}'.format(
        schema=szuru_config['database']['schema'],
        user=szuru_config['database']['user'],
        password=szuru_config['database']['pass'],
        host=szuru_config['database']['host'],
        port=szuru_config['database']['port'],
        name=szuru_config['database']['name']))

target_metadata = szurubooru.db.Base.metadata


def run_migrations_offline():
    '''
    Run migrations in 'offline' mode.

    This configures the context with just a URL
    and not an Engine, though an Engine is acceptable
    here as well.  By skipping the Engine creation
    we don't even need a DBAPI to be available.

    Calls to context.execute() here emit the given string to the
    script output.
    '''
    url = alembic_config.get_main_option('sqlalchemy.url')
    alembic.context.configure(
        url=url, target_metadata=target_metadata, literal_binds=True)

    with alembic.context.begin_transaction():
        alembic.context.run_migrations()


def run_migrations_online():
    '''
    Run migrations in 'online' mode.

    In this scenario we need to create an Engine
    and associate a connection with the context.
    '''
    connectable = sqlalchemy.engine_from_config(
        alembic_config.get_section(alembic_config.config_ini_section),
        prefix='sqlalchemy.',
        poolclass=sqlalchemy.pool.NullPool)

    with connectable.connect() as connection:
        alembic.context.configure(
            connection=connection,
            target_metadata=target_metadata)

        with alembic.context.begin_transaction():
            alembic.context.run_migrations()

if alembic.context.is_offline_mode():
    run_migrations_offline()
else:
    run_migrations_online()
