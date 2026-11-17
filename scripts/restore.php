<?php
/**
 * ChipiPie Cafe POS System — Automated CLI Database Restore Script
 *
 * Usage:
 *   php scripts/restore.php <backup_file_path> [--force]
 *
 * Options:
 *   --force       Skip interactive confirmation prompt
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../api/db_config.php';
require_once __DIR__ . '/../api/backup_helper.php';

$args = array_slice($argv, 1);
$force = false;
$filePath = null;

foreach ($args as $arg) {
    if ($arg === '--force' || $arg === '-f') {
        $force = true;
    } elseif (strpos($arg, '--') !== 0 && $filePath === null) {
        $filePath = $arg;
    }
}

if (!$filePath) {
    echo "ChipiPie Cafe POS — Database Restore Tool\n";
    echo "Usage:\n";
    echo "  php scripts/restore.php <path-to-backup-file> [--force]\n\n";
    echo "Available server backups:\n";
    $backups = ChipipieBackupManager::listBackups();
    if (empty($backups)) {
        echo "  (No backups found in backups/ directory)\n";
    } else {
        foreach ($backups as $b) {
            echo "  - backups/{$b['filename']} ({$b['size_formatted']}, {$b['created_at']})\n";
        }
    }
    exit(1);
}

// Check relative to current working directory or relative to backups/
if (!file_exists($filePath)) {
    $altPath = ChipipieBackupManager::getBackupDirectory() . DIRECTORY_SEPARATOR . basename($filePath);
    if (file_exists($altPath)) {
        $filePath = $altPath;
    } else {
        echo "[ERROR] Backup file not found: {$filePath}\n";
        exit(1);
    }
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (!in_array($ext, ['sql', 'json'])) {
    echo "[ERROR] Unsupported backup extension '.{$ext}'. Only .sql and .json files are supported.\n";
    exit(1);
}

echo "========================================================\n";
echo "  WARNING: ChipiPie Cafe POS Database Restoration\n";
echo "========================================================\n";
echo "Target Database: " . DB_NAME . " on " . DB_HOST . "\n";
echo "Backup File:     " . realpath($filePath) . "\n";
echo "File Size:       " . ChipipieBackupManager::formatBytes(filesize($filePath)) . "\n";
echo "Format:          " . strtoupper($ext) . "\n";
echo "--------------------------------------------------------\n";
echo "ATTENTION: This will OVERWRITE and RESTORE all system\n";
echo "tables, orders, menu items, settings, and accounts to\n";
echo "the state stored in this backup file!\n";
echo "========================================================\n";

if (!$force) {
    echo "Are you ABSOLUTELY sure you want to proceed? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    if (strtolower($line) !== 'yes') {
        echo "Restoration aborted by user.\n";
        exit(0);
    }
}

echo "Starting restoration process...\n";
$startTime = microtime(true);

try {
    if ($ext === 'json') {
        $res = ChipipieBackupManager::restoreJson($filePath);
        echo "[SUCCESS] JSON Restoration Completed!\n";
        echo "  Tables Restored:  {$res['tables_restored']}\n";
        echo "  Records Restored: {$res['rows_restored']}\n";
        echo "  Duration:         {$res['duration_seconds']}s\n";
    } else {
        $res = ChipipieBackupManager::restoreSql($filePath);
        echo "[SUCCESS] SQL Restoration Completed!\n";
        echo "  Statements Run:   {$res['statements_executed']}\n";
        echo "  Duration:         {$res['duration_seconds']}s\n";
        if (!empty($res['errors'])) {
            echo "  Warnings/Errors:  " . count($res['errors']) . "\n";
        }
    }
    echo "========================================================\n";
    exit(0);
} catch (\Throwable $e) {
    echo "[ERROR] Restoration failed: " . $e->getMessage() . "\n";
    exit(1);
}
