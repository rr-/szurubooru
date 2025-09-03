#!/usr/bin/dumb-init /bin/sh
set -e
cd /opt/app

alembic upgrade head

echo "Starting szurubooru API on port ${PORT} - Running on ${THREADS} threads"
exec hupper -m waitress --port ${PORT} --threads ${THREADS} szurubooru.facade:app
