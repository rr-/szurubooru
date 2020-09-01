#!/usr/bin/dumb-init /bin/sh

# Integrate environment variables
sed -i "s|__BACKEND__|${BACKEND_HOST}|" \
    /etc/nginx/nginx.conf
sed -i "s|__BASEURL__|${BASE_URL:-/}|g" \
    /var/www/index.htm \
    /var/www/manifest.json

# Start server
exec nginx
