#!/usr/bin/dumb-init /bin/sh

# Integrate environment variables
sed -i "s|__BACKEND__|${BACKEND_HOST}|" \
    /etc/nginx/nginx.conf

# Start server
nginx&

# Watch source for changes and build app
npm run watch -- --polling
