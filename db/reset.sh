#!/bin/bash
set -e

ROOT_DIR=$(git rev-parse --show-toplevel)
DB="${ROOT_DIR}/var/data/db.sqlite3"

mkdir -p "${ROOT_DIR}/var/data"

# delete existing database
rm -f "$DB"

# create new database and tables
sqlite3 "$DB" -bail < "${ROOT_DIR}/db/schema.sql"

# seed reference data
sqlite3 "$DB" -bail < "${ROOT_DIR}/db/seed/countries.sql"
sqlite3 "$DB" -bail < "${ROOT_DIR}/db/seed/motogp-calendar-2026.sql"
sqlite3 "$DB" -bail < "${ROOT_DIR}/db/seed/motogp-riders-2026.sql"

# seed test data
sqlite3 "$DB" -bail < "${ROOT_DIR}/db/test-data/results.sql"
sqlite3 "$DB" -bail < "${ROOT_DIR}/db/test-data/users.sql"
