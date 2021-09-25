#!/usr/bin/dumb-init /bin/sh

# Create cache directory
mkdir -p /tmp/nginx-cache
chmod a+rwx /tmp/nginx-cache

# Integrate environment variables
sed -i "s|__BACKEND__|${BACKEND_HOST}|" \
    /etc/nginx/nginx.conf

# Start server
exec nginx
