#!/bin/bash
set -euo pipefail

ROOT_DIR=$(git rev-parse --show-toplevel)
DB="${ROOT_DIR}/var/data/db.sqlite3"

HOST="motogp-test"
REMOTE_DB="/var/www/motogp/var/data/db.sqlite3"

echo "Creating fresh test database..."
"${ROOT_DIR}/db/reset.sh"

echo "Copying database to test server..."
rsync \
    --archive \
    --human-readable \
    "${DB}" "${HOST}:${REMOTE_DB}"

ssh "${HOST}" \
    "chgrp www-data '${REMOTE_DB}' && chmod 664 '${REMOTE_DB}'"

echo "Test database reset complete."
