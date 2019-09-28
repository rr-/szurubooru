FROM python:3.6-slim
WORKDIR /opt/app

COPY requirements.txt ./requirements.txt
RUN \
    # Install ffmpeg
    apt-get -yqq update && \
    apt-get -yq install --no-install-recommends ffmpeg && \
    rm -rf /var/lib/apt/lists/* && \
    # Install waitress
    pip3 install --no-cache-dir waitress && \
    # Install app requirements
    pip3 install --no-cache-dir -r ./requirements.txt

COPY ./ /opt/app/

ARG PUID=1000
ARG PGID=1000
RUN \
    # Set users
    mkdir -p /opt/app /data && \
    groupadd -g ${PGID} app && \
    useradd -d /opt/app -M -c '' -g app -r -u ${PUID} app && \
    chown -R app:app /opt/app /data
USER app

ENV PORT=6666
EXPOSE ${PORT}
VOLUME ["/data/"]
CMD ["/opt/app/docker-start.sh"]
