#!/bin/bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "$0")/.." && pwd)
BACKUP="${1:?Usage: db/restore.sh BACKUP_FILE [DATABASE_FILE]}"
DB="${2:-${ROOT_DIR}/var/data/db.sqlite3}"

if [[ ! -f "$BACKUP" ]]; then
    echo "Backup not found: $BACKUP" >&2
    exit 1
fi

if [[ "$(sqlite3 "$BACKUP" 'PRAGMA integrity_check;')" != "ok" ]]; then
    echo "Backup failed integrity check." >&2
    exit 1
fi

echo "Restore $BACKUP"
echo "     to $DB"
echo
echo "WARNING: Existing database contents will be replaced."
read -r -p "Type RESTORE to continue: " CONFIRM

if [[ "$CONFIRM" != "RESTORE" ]]; then
    echo "Restore cancelled."
    exit 1
fi

mkdir -p "$(dirname "$DB")"

sqlite3 "$DB" ".restore '$BACKUP'"

echo "Database restored successfully."
