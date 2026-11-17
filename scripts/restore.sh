#!/usr/bin/env bash
# ==============================================================================
# ChipiPie Cafe POS System — CLI Database Restore Helper (DigitalOcean Droplet)
# ==============================================================================
# Usage:
#   bash scripts/restore.sh <backup_file_path> [--force]
# ==============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"

cd "$APP_DIR"
php "$SCRIPT_DIR/restore.php" "$@"
