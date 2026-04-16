#!/bin/bash
set -euo pipefail

DEST_DIR="/var/www/motogp"

ROOT_DIR=$(git rev-parse --show-toplevel)
EXCLUDE_FILE="${ROOT_DIR}/bin/deploy-exclude.txt"

if ! command -v rsync >/dev/null 2>&1; then
    echo "Error: rsync is required but was not found in PATH." >&2
    exit 1
fi

if [ ! -f "${EXCLUDE_FILE}" ]; then
    echo "Error: exclude file not found at ${EXCLUDE_FILE}." >&2
    exit 1
fi

mkdir -p "${DEST_DIR}"
mkdir -p "${DEST_DIR}/config"
mkdir -p "${DEST_DIR}/db"
mkdir -p "${DEST_DIR}/var/cache/mustache"
mkdir -p "${DEST_DIR}/var/log"

if [ ! -f "${ROOT_DIR}/config/app.ini" ]; then
    echo "Error: ${ROOT_DIR}/config/app.ini does not exist." >&2
    echo "Create the production config before deploying." >&2
    exit 1
fi

if [ ! -d "${ROOT_DIR}/vendor" ]; then
    echo "Error: ${ROOT_DIR}/vendor does not exist." >&2
    echo "Run composer install before deploying." >&2
    exit 1
fi

rsync \
    --archive \
    --compress \
    --delete \
    --human-readable \
    --no-owner \
    --no-group \
    --chown=:www-data \
    --exclude-from="${EXCLUDE_FILE}" \
    "${ROOT_DIR}/" "${DEST_DIR}/"

echo "Deploy complete: ${DEST_DIR}"
