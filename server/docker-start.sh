#!/bin/sh
set -e
cd /opt/app

alembic upgrade head

echo "Starting szurubooru API on port ${PORT}"
exec waitress-serve --port ${PORT} szurubooru.facade:app