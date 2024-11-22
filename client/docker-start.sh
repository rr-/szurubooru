#!/usr/bin/dumb-init /bin/sh

# Integrate environment variables
sed -i "s|__BACKEND__|${BACKEND_HOST}|" \
    /etc/nginx/nginx.conf
sed -i "s|__BASEURL__|${BASE_URL:-/}|g" \
    /var/www/index.htm \
    /var/www/manifest.json

# Start server
echo "$0 starting server..."
exec openresty -c /etc/nginx/nginx.conf
