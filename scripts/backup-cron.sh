#!/bin/bash
# TOEFL House ERP v3 — Database Backup Cron Script
# Per 13_INFRASTRUCTURE_AND_DEPLOYMENT.md §8
#
# Install as cron job:
#   0 2 * * * /path/to/toefl-house-v3/scripts/backup-cron.sh
#
# This runs daily at 2:00 AM with tiered retention:
# - Daily backups: retained for 30 days
# - Monthly backups (1st of month): retained for 1 year

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")/server"

cd "$PROJECT_DIR"

echo "[$(date)] Starting database backup..."

php artisan backup:database --tiered

echo "[$(date)] Backup complete."
