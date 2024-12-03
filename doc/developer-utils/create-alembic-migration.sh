#!/bin/sh

# Helper script to create an alembic migration file via Docker

if [ $# -lt 1 ]; then
    echo "Need to pass a name for your migration file" > /dev/stderr
    exit 1
fi

# Create a dummy container
WORKDIR="$(git rev-parse --show-toplevel)/server"
IMAGE=$(docker build -q "${WORKDIR}")
CONTAINER=$(docker run --network=host -d ${IMAGE} tail -f /dev/null)

# Create the migration script
docker exec -i \
    -e PYTHONPATH='/opt/app' \
    -e POSTGRES_HOST='localhost' \
    -e POSTGRES_PORT='15432' \
    -e POSTGRES_USER='szuru' \
    -e POSTGRES_PASSWORD='changeme' \
    ${CONTAINER} alembic revision --autogenerate -m "$1"

# Copy the file over from the container
docker cp ${CONTAINER}:/opt/app/szurubooru/migrations/versions/ \
    "${WORKDIR}/szurubooru/migrations/"

# Destroy the dummy container
docker rm -f ${CONTAINER} > /dev/null
