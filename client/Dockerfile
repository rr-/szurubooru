FROM node:9 as builder
WORKDIR /opt/app

COPY package.json ./
RUN npm install

COPY . ./

ARG BUILD_INFO="docker-latest"
ARG CLIENT_BUILD_ARGS=""
RUN BASE_URL="__BASEURL__" node build.js --gzip ${CLIENT_BUILD_ARGS}


FROM scratch as approot

COPY docker-start.sh /

WORKDIR /etc/nginx
COPY nginx.conf.docker ./nginx.conf

WORKDIR /var/www
COPY --from=builder /opt/app/public/ .


FROM nginx:alpine

RUN apk --no-cache add dumb-init
COPY --from=approot / /

CMD ["/docker-start.sh"]
VOLUME ["/data"]
