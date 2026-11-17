#!/usr/bin/env bash
# ==============================================================================
# ChipiPie Cafe POS System — Automated Scheduled Backup Script (DigitalOcean Droplet)
# ==============================================================================
# Scheduled via crontab:
# 0 * * * * /bin/bash /var/www/html/CHIPIPIE_SYSTEM/scripts/backup.sh >> /var/www/html/CHIPIPIE_SYSTEM/logs/backup.log 2>&1
# ==============================================================================

set -e

# Resolve script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
LOGS_DIR="$APP_DIR/logs"

# Ensure logs directory exists
mkdir -p "$LOGS_DIR"

cd "$APP_DIR"

# Run PHP Backup script (in --auto mode it checks UI settings; or pass custom args)
if [ $# -eq 0 ]; then
    php "$SCRIPT_DIR/backup.php" --auto
else
    php "$SCRIPT_DIR/backup.php" "$@"
fi
