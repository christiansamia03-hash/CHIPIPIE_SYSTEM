#!/usr/bin/env bash
# ==============================================================================
# ChipiPie Cafe POS System — Automated Daily Backup Script (DigitalOcean Droplet)
# ==============================================================================
# This script can be scheduled via crontab on DigitalOcean:
# 0 2 * * * /bin/bash /var/www/html/CHIPIPIE_SYSTEM/scripts/backup.sh >> /var/www/html/CHIPIPIE_SYSTEM/logs/backup.log 2>&1
# ==============================================================================

set -e

# Resolve script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
LOGS_DIR="$APP_DIR/logs"

# Ensure logs directory exists
mkdir -p "$LOGS_DIR"

echo "=========================================================="
echo " Starting ChipiPie POS Automated Database Backup"
echo " Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo " Base Path: $APP_DIR"
echo "=========================================================="

cd "$APP_DIR"

# Run PHP Backup script (purges backups older than 14 days automatically)
php "$SCRIPT_DIR/backup.php" --format=sql --keep-days=14

echo "Backup finished successfully at $(date '+%Y-%m-%d %H:%M:%S')"
echo "=========================================================="
