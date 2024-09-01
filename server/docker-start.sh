#!/usr/bin/dumb-init /bin/sh
set -e
cd /opt/app

mkdir -p /opt/app/bin
wget -O /opt/app/bin/yt-dlp https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp
chmod +x /opt/app/bin/yt-dlp
export PATH=/opt/app/bin:$PATH

alembic upgrade head

echo "Starting szurubooru API on port ${PORT} - Running on ${THREADS} threads"
exec waitress-serve-3 --port ${PORT} --threads ${THREADS} szurubooru.facade:app
