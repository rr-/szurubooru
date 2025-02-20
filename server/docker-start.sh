#!/bin/bash
set -e
# cd /opt/app

alembic upgrade head

echo "Starting szurubooru API on port ${PORT} - Running on ${THREADS} threads"
exec gunicorn --bind 0.0.0.0:${PORT} --threads ${THREADS} szurubooru.facade:app
