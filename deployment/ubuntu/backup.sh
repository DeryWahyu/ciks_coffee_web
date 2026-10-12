#!/usr/bin/env bash
set -euo pipefail
umask 0077
exec 9>/run/lock/cikscoffee-backup.lock
flock -n 9 || exit 0
backup=/var/backups/cikscoffee
install -d -m 0700 "$backup"
stamp=$(date -u +%Y%m%dT%H%M%SZ)
mariadb-dump --single-transaction --routines --triggers cikscoffee | gzip > "$backup/database-$stamp.sql.gz"
tar --dereference -czf "$backup/storage-new.tar.gz" -C /var/www/cikscoffee storage/app .env
mv -f "$backup/storage-new.tar.gz" "$backup/storage-latest.tar.gz"
find "$backup" -maxdepth 1 -type f -name 'database-*.sql.gz' -mtime +7 -delete
printf '%s\n' "BACKUP_OK $stamp"
