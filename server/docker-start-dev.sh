#!/usr/bin/dumb-init /bin/sh
set -e
cd /opt/app

alembic upgrade head

echo "Starting szurubooru API on port ${PORT}"
exec hupper -m waitress --port ${PORT} szurubooru.facade:app
