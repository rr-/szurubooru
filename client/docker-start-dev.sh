#!/usr/bin/dumb-init /bin/sh

# Integrate environment variables
sed -i "s|__BACKEND__|${BACKEND_HOST}|" \
  /etc/nginx/nginx.conf

# Start server
nginx &

# Watch source for changes and build app
# FIXME: It's not ergonomic to run `npm i` outside of the build step.
#        However, the mounting of different directories into the
#        client container's /opt/app causes node_modules to disappear
#          (the mounting causes client/Dockerfile's RUN npm install
#           to silently clobber).
#        Find a way to move `npm i` into client/Dockerfile.
npm i && npm run watch -- --polling
