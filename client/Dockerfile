FROM --platform=$BUILDPLATFORM node:lts-alpine as development
WORKDIR /opt/app

RUN apk --no-cache add \
    dumb-init \
    nginx \
    git

RUN ln -sf /opt/app/nginx.conf.docker /etc/nginx/nginx.conf
RUN rm -rf /var/www
RUN ln -sf /opt/app/public/ /var/www

COPY package.json package-lock.json ./
RUN npm install

ARG BUILD_INFO="docker-development"
ENV BUILD_INFO=${BUILD_INFO}
ENV BACKEND_HOST="server"

CMD ["/opt/app/docker-start-dev.sh"]
VOLUME ["/data"]


FROM --platform=$BUILDPLATFORM node:lts as builder
WORKDIR /opt/app

COPY package.json package-lock.json ./
RUN npm install

COPY . ./

ARG BUILD_INFO="docker-latest"
ARG CLIENT_BUILD_ARGS=""
RUN BASE_URL="__BASEURL__" node build.js --gzip ${CLIENT_BUILD_ARGS}


FROM --platform=$BUILDPLATFORM scratch as approot

COPY docker-start.sh /

WORKDIR /etc/nginx
COPY nginx.conf.docker ./nginx.conf

WORKDIR /var/www
COPY --from=builder /opt/app/public/ .


FROM nginx:alpine as release

RUN apk --no-cache add dumb-init
COPY --from=approot / /

CMD ["/docker-start.sh"]
VOLUME ["/data"]

ARG DOCKER_REPO
ARG BUILD_DATE
ARG SOURCE_COMMIT
LABEL \
    maintainer="" \
    org.opencontainers.image.title="${DOCKER_REPO}" \
    org.opencontainers.image.url="https://github.com/rr-/szurubooru" \
    org.opencontainers.image.documentation="https://github.com/rr-/szurubooru/blob/${SOURCE_COMMIT}/doc/INSTALL.md" \
    org.opencontainers.image.created="${BUILD_DATE}" \
    org.opencontainers.image.source="https://github.com/rr-/szurubooru" \
    org.opencontainers.image.revision="${SOURCE_COMMIT}" \
    org.opencontainers.image.licenses="GPL-3.0"
