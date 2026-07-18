#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# Black Door — Daily PostgreSQL Backup Script
# Usage: Run via cron inside the postgres container or from the host
# Example cron: 0 2 * * * docker exec blackdoor-postgres /backups/backup.sh
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

# ─── Configuration ───────────────────────────────────────────────────────────
BACKUP_DIR="/backups"
DB_NAME="${POSTGRES_DB:-blackdoor}"
DB_USER="${POSTGRES_USER:-blackdoor}"
RETENTION_DAYS=7
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILE="${BACKUP_DIR}/blackdoor_${TIMESTAMP}.sql.gz"

# ─── Create Backup Directory ────────────────────────────────────────────────
mkdir -p "${BACKUP_DIR}"

# ─── Perform Backup ─────────────────────────────────────────────────────────
echo "[$(date)] Starting backup of database '${DB_NAME}'..."

pg_dump \
    -U "${DB_USER}" \
    -d "${DB_NAME}" \
    --no-owner \
    --no-privileges \
    --format=plain \
    --verbose \
    2>/dev/null \
    | gzip > "${BACKUP_FILE}"

if [ $? -eq 0 ] && [ -s "${BACKUP_FILE}" ]; then
    FILESIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
    echo "[$(date)] Backup completed successfully: ${BACKUP_FILE} (${FILESIZE})"
else
    echo "[$(date)] ERROR: Backup failed!" >&2
    rm -f "${BACKUP_FILE}"
    exit 1
fi

# ─── Cleanup Old Backups ────────────────────────────────────────────────────
echo "[$(date)] Removing backups older than ${RETENTION_DAYS} days..."
DELETED=$(find "${BACKUP_DIR}" -name "blackdoor_*.sql.gz" -type f -mtime +${RETENTION_DAYS} -print -delete | wc -l)
echo "[$(date)] Deleted ${DELETED} old backup(s)."

echo "[$(date)] Backup process complete."
