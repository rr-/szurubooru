"""
Alembic setup and configuration script

isort:skip_file
"""


import logging.config
import os
import sys
from time import sleep

import alembic
import sqlalchemy as sa


# fmt: off
# make szurubooru module importable
dir_to_self = os.path.dirname(os.path.realpath(__file__))
sys.path.append(os.path.join(dir_to_self, *[os.pardir] * 2))

import szurubooru.config  # noqa: E402
import szurubooru.model.base  # noqa: E402
# fmt: on


alembic_config = alembic.context.config
logging.config.fileConfig(alembic_config.config_file_name)

szuru_config = szurubooru.config.config
alembic_config.set_main_option("sqlalchemy.url", szuru_config["database"])

target_metadata = szurubooru.model.Base.metadata


def run_migrations_offline():
    """
    Run migrations in 'offline' mode.

    This configures the context with just a URL
    and not an Engine, though an Engine is acceptable
    here as well.  By skipping the Engine creation
    we don't even need a DBAPI to be available.

    Calls to context.execute() here emit the given string to the
    script output.
    """
    url = alembic_config.get_main_option("sqlalchemy.url")
    alembic.context.configure(
        url=url,
        target_metadata=target_metadata,
        literal_binds=True,
        compare_type=True,
    )

    with alembic.context.begin_transaction():
        alembic.context.run_migrations()


def run_migrations_online():
    """
    Run migrations in 'online' mode.

    In this scenario we need to create an Engine
    and associate a connection with the context.
    """
    connectable = sa.engine_from_config(
        alembic_config.get_section(alembic_config.config_ini_section),
        prefix="sqlalchemy.",
        poolclass=sa.pool.NullPool,
    )

    def connect_with_timeout(connectable, timeout=45):
        dt = 5
        for _ in range(int(timeout / dt)):
            try:
                return connectable.connect()
            except sa.exc.OperationalError:
                sleep(dt)
        return connectable.connect()

    with connect_with_timeout(connectable) as connection:
        alembic.context.configure(
            connection=connection,
            target_metadata=target_metadata,
            compare_type=True,
        )

        with alembic.context.begin_transaction():
            alembic.context.run_migrations()


if alembic.context.is_offline_mode():
    run_migrations_offline()
else:
    run_migrations_online()
