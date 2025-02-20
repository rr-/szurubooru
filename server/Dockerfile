FROM ghcr.io/astral-sh/uv:python3.12-bookworm-slim AS prereqs

ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get -y update &&  \ 
    apt-get install --no-install-recommends  \
    -y ffmpeg && \
    rm -rf /var/lib/apt/lists/*

# Install the project into `/app`
WORKDIR /app

# Enable bytecode compilation
ENV UV_COMPILE_BYTECODE=1

# Copy from the cache instead of linking since it's a mounted volume
ENV UV_LINK_MODE=copy

# Install the project's dependencies using the lockfile and settings
RUN --mount=type=cache,target=/root/.cache/uv \
    --mount=type=bind,source=uv.lock,target=uv.lock \
    --mount=type=bind,source=pyproject.toml,target=pyproject.toml \
    uv sync --frozen --no-install-project --no-dev

# Then, add the rest of the project source code and install it
# Installing separately from its dependencies allows optimal layer caching
ADD . /app
RUN --mount=type=cache,target=/root/.cache/uv \
    uv sync --frozen --no-dev

ENV PATH="/app/.venv/bin:$PATH"
# Reset the entrypoint, don't invoke `uv`
ENTRYPOINT []


FROM prereqs AS testing

RUN apt-get -y update &&  \ 
    apt-get install --no-install-recommends  \
    -y postgresql && \
    rm -rf /var/lib/apt/lists/* \
    && groupadd app \
    && useradd -g app app \
    && chown app:app /app

WORKDIR /app

# Install dev dependencies
RUN --mount=type=cache,target=/root/.cache/uv \
    uv sync --frozen
# COPY ./szurubooru/tests /opt/app/szurubooru/tests/

ENV TEST_ENVIRONMENT="true"
USER app
ENTRYPOINT ["pytest", "--tb=short"]
CMD ["szurubooru/"]


FROM prereqs AS release
WORKDIR /app

ARG PUID=1000
ARG PGID=1000

RUN mkdir -p /data \
    && groupadd -g ${PGID} app \
    && useradd -r -u ${PUID} -g app app \
    && chown -R app:app /app /data

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
