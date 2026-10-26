#!/bin/bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "$0")/.." && pwd)
DB="${2:-${ROOT_DIR}/var/data/db.sqlite3}"
BACKUP="${1:?Usage: db/dump.sh BACKUP_FILE [DATABASE_FILE]}"

if [[ ! -f "$DB" ]]; then
    echo "Database not found: $DB" >&2
    exit 1
fi

if [[ -e "$BACKUP" ]]; then
    echo "Backup already exists: $BACKUP" >&2
    exit 1
fi

mkdir -p "$(dirname "$BACKUP")"

sqlite3 "$DB" ".backup '$BACKUP'"

if [[ "$(sqlite3 "$BACKUP" 'PRAGMA integrity_check;')" != "ok" ]]; then
    echo "Backup failed integrity check." >&2
    rm -f "$BACKUP"
    exit 1
fi

echo "Database backed up to $BACKUP"
