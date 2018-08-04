FROM scratch as approot
WORKDIR /opt/app

COPY alembic.ini wait-for-es generate-thumb ./
COPY szurubooru/ ./szurubooru/
COPY config.yaml.dist ./


FROM python:3.6-slim
WORKDIR /opt/app

ARG PUID=1000
ARG PGID=1000
ARG PORT=6666
RUN \
    # Set users
    mkdir -p /opt/app /data && \
    groupadd -g ${PGID} app && \
    useradd -d /opt/app -M -c '' -g app -r -u ${PUID} app && \
    chown -R app:app /opt/app /data && \
    # Create init file
    echo "#!/bin/sh" >> /init && \
    echo "set -e" >> /init && \
    echo "cd /opt/app" >> /init && \
    echo "./wait-for-es" >> /init && \
    echo "alembic upgrade head" >> /init && \
    echo "exec waitress-serve --port ${PORT} szurubooru.facade:app" \
        >> /init && \
    chmod a+x /init && \
    # Install ffmpeg
    apt-get -yqq update && \
    apt-get -yq install --no-install-recommends ffmpeg && \
    rm -rf /var/lib/apt/lists/* && \
    # Install waitress
    pip3 install --no-cache-dir waitress

COPY --chown=app:app requirements.txt ./requirements.txt
RUN pip3 install --no-cache-dir -r ./requirements.txt

# done to minimize number of layers in final image
COPY --chown=app:app --from=approot / /

VOLUME ["/data/"]
EXPOSE ${PORT}
USER app
CMD ["/init"]
