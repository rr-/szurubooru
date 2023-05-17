ARG ALPINE_VERSION=3.13


FROM alpine:$ALPINE_VERSION as prereqs
WORKDIR /opt/app

RUN apk --no-cache add \
        python3 \
        python3-dev \
        py3-pip \
        build-base \
        libheif \
        libheif-dev \
        libavif \
        libavif-dev \
        ffmpeg \
        # from requirements.txt:
        py3-yaml \
        py3-psycopg2 \
        py3-sqlalchemy \
        py3-certifi \
        py3-numpy \
        py3-pillow \
        py3-pynacl \
        py3-tz \
        py3-pyrfc3339 \
 && pip3 install --no-cache-dir --disable-pip-version-check \
        "alembic>=0.8.5" \
        "coloredlogs==5.0" \
        gallery_dl \
        "pyheif==0.6.1" \
        "heif-image-plugin>=0.3.2" \
        youtube_dl \
        "pillow-avif-plugin>=1.1.0" \
 && apk --no-cache del py3-pip

COPY ./ /opt/app/
RUN rm -rf /opt/app/szurubooru/tests


FROM --platform=$BUILDPLATFORM prereqs as testing
WORKDIR /opt/app

RUN apk --no-cache add \
        py3-pip \
        py3-pytest \
        py3-pytest-cov \
        postgresql \
 && pip3 install --no-cache-dir --disable-pip-version-check \
        pytest-pgsql \
        freezegun \
 && apk --no-cache del py3-pip \
 && addgroup app \
 && adduser -SDH -h /opt/app -g '' -G app app \
 && chown app:app /opt/app

COPY --chown=app:app ./szurubooru/tests /opt/app/szurubooru/tests/

ENV TEST_ENVIRONMENT="true"
USER app
ENTRYPOINT ["pytest", "--tb=short"]
CMD ["szurubooru/"]


FROM prereqs as release
WORKDIR /opt/app

ARG PUID=1000
ARG PGID=1000

RUN apk --no-cache add \
        dumb-init \
        py3-setuptools \
        py3-waitress \
 && mkdir -p /opt/app /data \
 && addgroup -g ${PGID} app \
 && adduser -SDH -h /opt/app -g '' -G app -u ${PUID} app \
 && chown -R app:app /opt/app /data

USER app
CMD ["/opt/app/docker-start.sh"]

ARG PORT=6666
ENV PORT=${PORT}
EXPOSE ${PORT}

ARG THREADS=4
ENV THREADS=${THREADS}

VOLUME ["/data/"]

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
