<?php
/**
 * ChipiPie Cafe POS System — Automated CLI Database Backup Script
 *
 * Usage:
 *   php scripts/backup.php [options]
 *
 * Options:
 *   --format=sql|json    Export format (default: sql)
 *   --keep-days=14       Automatically purge backups older than N days (default: 14)
 *   --output=<path>      Custom target file path
 *   --skip-sessions      Do not export session records
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../api/db_config.php';
require_once __DIR__ . '/../api/backup_helper.php';

// Parse CLI options
$options = getopt('', ['format::', 'keep-days::', 'output::', 'skip-sessions', 'help']);

if (isset($options['help'])) {
    echo "ChipiPie Cafe POS — Automated Database Backup\n";
    echo "Usage: php scripts/backup.php [options]\n";
    echo "  --format=sql|json   Format (default: sql)\n";
    echo "  --keep-days=N       Purge backups older than N days (default: 14)\n";
    echo "  --output=<path>     Custom output path\n";
    echo "  --skip-sessions     Exclude session records\n";
    exit(0);
}

$format       = ($options['format'] ?? 'sql') === 'json' ? 'json' : 'sql';
$keepDays     = isset($options['keep-days']) ? max(1, (int)$options['keep-days']) : 14;
$skipSessions = isset($options['skip-sessions']);
$customOutput = $options['output'] ?? null;

echo "========================================================\n";
echo "  ChipiPie Cafe POS — CLI Database Backup\n";
echo "========================================================\n";
echo "Time:     " . date('Y-m-d H:i:s') . "\n";
echo "Database: " . DB_NAME . " on " . DB_HOST . "\n";
echo "Format:   " . strtoupper($format) . "\n";
echo "--------------------------------------------------------\n";

try {
    $startTime = microtime(true);

    if ($customOutput) {
        $targetFile = $customOutput;
        if ($format === 'json') {
            ChipipieBackupManager::exportJson($targetFile);
        } else {
            ChipipieBackupManager::exportSql($targetFile, ['skip_sessions' => $skipSessions]);
        }
        $filename = basename($targetFile);
        $size = filesize($targetFile);
    } else {
        $result = ChipipieBackupManager::createServerBackup($format);
        $targetFile = $result['filepath'];
        $filename   = $result['filename'];
        $size       = $result['size'];
    }

    $duration = round(microtime(true) - $startTime, 2);
    $sizeFormatted = ChipipieBackupManager::formatBytes($size);

    echo "[SUCCESS] Backup created successfully!\n";
    echo "  File:     {$filename}\n";
    echo "  Path:     {$targetFile}\n";
    echo "  Size:     {$sizeFormatted} ({$size} bytes)\n";
    echo "  Duration: {$duration}s\n";

    // Auto-clean old backups
    echo "--------------------------------------------------------\n";
    echo "Checking for old backups (> {$keepDays} days)...\n";
    $dir = ChipipieBackupManager::getBackupDirectory();
    $cutoff = time() - ($keepDays * 86400);
    $deletedCount = 0;

    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..' || $f === '.htaccess' || $f === 'index.php') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $f;
        if (is_file($path) && filemtime($path) < $cutoff) {
            @unlink($path);
            echo "  Purged old backup: {$f}\n";
            $deletedCount++;
        }
    }

    if ($deletedCount === 0) {
        echo "  No old backups to purge.\n";
    } else {
        echo "  Purged {$deletedCount} old backup file(s).\n";
    }

    echo "========================================================\n";
    exit(0);

} catch (\Throwable $e) {
    echo "[ERROR] Backup failed: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
