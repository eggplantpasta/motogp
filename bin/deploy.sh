#!/bin/bash
set -euo pipefail

ROOT_DIR=$(git rev-parse --show-toplevel)
BUILD_DIR="${ROOT_DIR}/tmp/deploy"
EXCLUDE_FILE="${ROOT_DIR}/bin/deploy-exclude.txt"

ENVIRONMENT="${1:-}"

DRY_RUN="${2:-}"

RSYNC_OPTIONS=()

if [ "${DRY_RUN}" = "--dry-run" ]; then
    RSYNC_OPTIONS+=(--dry-run --itemize-changes)
elif [ -n "${DRY_RUN}" ]; then
    echo "Usage: $0 test [--dry-run]" >&2
    exit 1
fi

case "${ENVIRONMENT}" in
    test)
        HOST="motogp-test"
        DEST_DIR="/var/www/motogp"
        ;;
    *)
        echo "Usage: $0 test" >&2
        exit 1
        ;;
esac

if ! command -v rsync >/dev/null 2>&1; then
    echo "Error: rsync is required but was not found in PATH." >&2
    exit 1
fi

if [ ! -f "${EXCLUDE_FILE}" ]; then
    echo "Error: exclude file not found at ${EXCLUDE_FILE}." >&2
    exit 1
fi

if [ ! -d "${ROOT_DIR}/vendor" ]; then
    echo "Error: ${ROOT_DIR}/vendor does not exist." >&2
    echo "Run composer install before deploying." >&2
    exit 1
fi

echo "Building deployment..."

rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}"

rsync \
    --archive \
    --delete \
    --exclude-from="${EXCLUDE_FILE}" \
    "${ROOT_DIR}/" "${BUILD_DIR}/"

composer install \
    --working-dir="${BUILD_DIR}" \
    --no-dev \
    --classmap-authoritative \
    --no-interaction

echo "Deployment build complete."

echo "Deploying to ${ENVIRONMENT} (${HOST}:${DEST_DIR})..."

ssh "${HOST}" \
    "mkdir -p \
        '${DEST_DIR}' \
        '${DEST_DIR}/config' \
        '${DEST_DIR}/var/data' \
        '${DEST_DIR}/var/cache/mustache' \
        '${DEST_DIR}/var/log'"

rsync \
    "${RSYNC_OPTIONS[@]}" \
    --archive \
    --compress \
    --delete \
    --human-readable \
    --no-owner \
    --no-group \
    --exclude-from="${EXCLUDE_FILE}" \
    "${BUILD_DIR}/" "${HOST}:${DEST_DIR}/"

echo "Deploy complete: ${ENVIRONMENT}"
