FROM alpine:latest
WORKDIR /opt/app

RUN \
    apk --no-cache add \
        python3 \
        dumb-init \
        ffmpeg \
        py3-waitress \
        # from requirements.txt:
        py3-yaml \
        py3-psycopg2 \
        py3-sqlalchemy \
        py3-certifi \
        py3-numpy \
        py3-pillow \
        py3-pynacl \
        py3-tz \
        py3-rfc3339 \
    && \
    pip3 install --no-cache-dir --disable-pip-version-check \
        alembic \
        "coloredlogs==5.0" \
        "elasticsearch>=5.0.0,<7.0.0" \
        "elasticsearch-dsl>=5.0.0,<7.0.0" \
    && exit 0

ARG PUID=1000
ARG PGID=1000
RUN \
    # Set users
    mkdir -p /opt/app /data && \
    addgroup -g ${PGID} app && \
    adduser -SDH -h /opt/app -g '' -G app -u ${PUID} app && \
    chown -R app:app /opt/app /data
USER app

COPY --chown=app:app ./ /opt/app/

ARG PORT=6666
ENV PORT=${PORT}
EXPOSE ${PORT}
VOLUME ["/data/"]
CMD ["/opt/app/docker-start.sh"]

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
