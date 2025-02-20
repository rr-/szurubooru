FROM ghcr.io/astral-sh/uv:python3.12-bookworm-slim AS builder
ENV UV_COMPILE_BYTECODE=1 UV_LINK_MODE=copy

# Disable Python downloads, because we want to use the system interpreter
# across both images. If using a managed Python version, it needs to be
# copied from the build image into the final image; see `standalone.Dockerfile`
# for an example.
ENV UV_PYTHON_DOWNLOADS=0

WORKDIR /app
RUN --mount=type=cache,target=/root/.cache/uv \
    --mount=type=bind,source=uv.lock,target=uv.lock \
    --mount=type=bind,source=pyproject.toml,target=pyproject.toml \
    uv sync --frozen --no-install-project --no-dev
ADD . /app
RUN --mount=type=cache,target=/root/.cache/uv \
    uv sync --frozen --no-dev


# RUN apk --no-cache add \
#         python3 \
#         python3-dev \
#         py3-pip \
#         build-base \
#         libheif \
#         libheif-dev \
#         libavif \
#         libavif-dev \
#         ffmpeg \
#         # from requirements.txt:
#         py3-yaml \
#         py3-psycopg2 \
#         py3-sqlalchemy \
#         py3-certifi \
#         py3-numpy \
#         py3-pillow \
#         py3-pynacl \
#         py3-tz \
#         py3-pyrfc3339
# RUN pip3 install --no-cache-dir --disable-pip-version-check \
#         "alembic>=0.8.5" \
#         "coloredlogs==5.0" \
#         "pyheif==0.6.1" \
#         "heif-image-plugin>=0.3.2" \
#         yt-dlp \
#         "pillow-avif-plugin~=1.1.0"
# RUN apk --no-cache del py3-pip

# COPY ./ /opt/app/
# RUN rm -rf /opt/app/szurubooru/tests


# FROM --platform=$BUILDPLATFORM prereqs as testing
# WORKDIR /opt/app

# RUN apk --no-cache add \
#         py3-pip \
#         py3-pytest \
#         py3-pytest-cov \
#         postgresql \
#  && pip3 install --no-cache-dir --disable-pip-version-check \
#         pytest-pgsql \
#         freezegun \
#  && apk --no-cache del py3-pip \
#  && addgroup app \
#  && adduser -SDH -h /opt/app -g '' -G app app \
#  && chown app:app /opt/app

# COPY --chown=app:app ./szurubooru/tests /opt/app/szurubooru/tests/

# ENV TEST_ENVIRONMENT="true"
# USER app
# ENTRYPOINT ["pytest", "--tb=short"]
# CMD ["szurubooru/"]


FROM python:3.12-slim-bookworm AS release
WORKDIR /app

ENV DEBIAN_FRONTEND noninteractive
RUN apt-get -y update &&  \ 
    apt-get install --no-install-recommends  \
    -y ffmpeg && \
    rm -rf /var/lib/apt/lists/*

ARG PUID=1000
ARG PGID=1000

# RUN apk --no-cache add \
# dumb-init \
# py3-setuptools \
# py3-waitress \
# RUN mkdir -p /data \
#         && addgroup -gid ${PGID} app \
#         && adduser -SDH -h /app -g '' -G app -u ${PUID} app \
#         && chown -R app:app /app /data

RUN mkdir -p /data \
    && groupadd -g ${PGID} app \
    && useradd -r -u ${PUID} -g app app \
    && chown -R app:app /app /data

# Copy the application from the builder
COPY --from=builder --chown=app:app /app /app

# Place executables in the environment at the front of the path
ENV PATH="/app/.venv/bin:$PATH"

USER app
CMD ["/app/docker-start.sh"]

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
