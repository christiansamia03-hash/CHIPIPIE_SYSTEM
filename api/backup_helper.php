<?php
/**
 * ChipiPie Cafe POS System — Unified Backup & Restore Engine
 * Handles full database dumps (SQL & JSON), server-side backup archives,
 * and transactional / safe database restoration.
 */

require_once __DIR__ . '/db_config.php';

class ChipipieBackupManager {

    /**
     * Get or create the backups storage directory.
     * Guarantees an .htaccess file exists to block all direct web access.
     */
    public static function getBackupDirectory() {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Security: Block direct web access
        $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            $htContent = "# Apache Security - Block all direct web downloads of backups\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n";
            @file_put_contents($htaccess, $htContent);
        }

        $indexPhp = $dir . DIRECTORY_SEPARATOR . 'index.php';
        if (!file_exists($indexPhp)) {
            @file_put_contents($indexPhp, "<?php http_response_code(403); exit('Direct access forbidden.');\n");
        }

        return $dir;
    }

    /**
     * Get all database tables and statistics
     */
    public static function getDatabaseStats() {
        $db = getDB();
        $tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
        
        $tableStats = [];
        $totalRows = 0;

        foreach ($tables as $tRow) {
            $tableName = $tRow[0];
            try {
                $count = (int)$db->query("SELECT COUNT(*) FROM `{$tableName}`")->fetchColumn();
            } catch (\Throwable $e) {
                $count = 0;
            }
            $tableStats[$tableName] = $count;
            $totalRows += $count;
        }

        // Check recent server backup
        $recentBackup = null;
        $backups = self::listBackups();
        if (!empty($backups)) {
            $recentBackup = $backups[0];
        }

        return [
            'database_name' => DB_NAME,
            'database_host' => DB_HOST,
            'table_count'   => count($tableStats),
            'total_rows'    => $totalRows,
            'tables'        => $tableStats,
            'last_backup'   => $recentBackup
        ];
    }

    /**
     * Generate a complete, self-contained SQL Dump.
     * Can stream directly to file pointer, write to a path, or return as string.
     */
    public static function exportSql($outputPath = null, $options = []) {
        $db = getDB();
        $skipSessions = !empty($options['skip_sessions']);

        // Determine destination handle
        $closeHandle = false;
        if ($outputPath === null || $outputPath === 'php://output') {
            $fp = fopen('php://output', 'w');
        } elseif (is_resource($outputPath)) {
            $fp = $outputPath;
        } else {
            $fp = fopen($outputPath, 'w');
            $closeHandle = true;
        }

        if (!$fp) {
            throw new Exception("Unable to open output stream for SQL export.");
        }

        $dateStr = date('Y-m-d H:i:s');
        $dbName = DB_NAME;
        $host = DB_HOST;

        // Write SQL Header
        fwrite($fp, "-- ============================================================\n");
        fwrite($fp, "-- ChipiPie Cafe POS System — Full Database Backup\n");
        fwrite($fp, "-- Generated at: {$dateStr} (Asia/Manila)\n");
        fwrite($fp, "-- Database: {$dbName} on {$host}\n");
        fwrite($fp, "-- PHP Version: " . PHP_VERSION . "\n");
        fwrite($fp, "-- ============================================================\n\n");

        fwrite($fp, "SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;\n");
        fwrite($fp, "SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;\n");
        fwrite($fp, "SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;\n");
        fwrite($fp, "SET NAMES utf8mb4;\n");
        fwrite($fp, "SET @OLD_TIME_ZONE=@@TIME_ZONE;\n");
        fwrite($fp, "SET TIME_ZONE='+00:00';\n");
        fwrite($fp, "SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;\n");
        fwrite($fp, "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;\n");
        fwrite($fp, "SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
        fwrite($fp, "SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, AUTOCOMMIT=0;\n\n");

        $tablesResult = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);

        foreach ($tablesResult as $row) {
            $table = $row[0];

            fwrite($fp, "-- ------------------------------------------------------------\n");
            fwrite($fp, "-- Table structure and data for table `{$table}`\n");
            fwrite($fp, "-- ------------------------------------------------------------\n");

            // Drop and create table statement
            fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");
            
            $createRow = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            $createTableSql = $createRow[1] ?? '';
            
            // Clean up auto-increment numbers in table options if desired, or keep exact
            fwrite($fp, $createTableSql . ";\n\n");

            // If user opted to skip php_sessions data, write empty table
            if ($skipSessions && $table === 'php_sessions') {
                fwrite($fp, "-- Skipped session table data\n\n");
                continue;
            }

            // Fetch rows in batches of 100 to conserve RAM and handle large blobs/base64
            $countStmt = $db->query("SELECT COUNT(*) FROM `{$table}`");
            $totalCount = (int)$countStmt->fetchColumn();

            if ($totalCount > 0) {
                fwrite($fp, "LOCK TABLES `{$table}` WRITE;\n");
                fwrite($fp, "/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n");

                $batchSize = 100;
                $offset = 0;

                while ($offset < $totalCount) {
                    $rowsStmt = $db->query("SELECT * FROM `{$table}` LIMIT {$batchSize} OFFSET {$offset}");
                    $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($rows)) {
                        break;
                    }

                    $columns = array_keys($rows[0]);
                    $colList = '`' . implode('`, `', $columns) . '`';

                    $valLines = [];
                    foreach ($rows as $r) {
                        $escapedVals = [];
                        foreach ($r as $val) {
                            if ($val === null) {
                                $escapedVals[] = 'NULL';
                            } else {
                                $escapedVals[] = $db->quote($val);
                            }
                        }
                        $valLines[] = '(' . implode(', ', $escapedVals) . ')';
                    }

                    fwrite($fp, "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $valLines) . ";\n");
                    $offset += count($rows);
                }

                fwrite($fp, "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\n");
                fwrite($fp, "UNLOCK TABLES;\n\n");
            }
        }

        // SQL Footer
        fwrite($fp, "COMMIT;\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;\n");
        fwrite($fp, "SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;\n");
        fwrite($fp, "SET AUTOCOMMIT=@OLD_AUTOCOMMIT;\n");
        fwrite($fp, "SET SQL_MODE=@OLD_SQL_MODE;\n");
        fwrite($fp, "SET TIME_ZONE=@OLD_TIME_ZONE;\n");
        fwrite($fp, "SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;\n");
        fwrite($fp, "SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;\n");
        fwrite($fp, "SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;\n");
        fwrite($fp, "-- End of backup\n");

        if ($closeHandle) {
            fclose($fp);
        }

        return true;
    }

    /**
     * Generate JSON Export of all data
     */
    public static function exportJson($outputPath = null) {
        $db = getDB();
        $tablesResult = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);

        $dump = [
            'system'       => 'CHIPIPIE_SYSTEM',
            'version'      => '2.0',
            'generated_at' => date('Y-m-d H:i:s'),
            'database'     => DB_NAME,
            'host'         => DB_HOST,
            'tables'       => []
        ];

        foreach ($tablesResult as $row) {
            $table = $row[0];
            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            $dump['tables'][$table] = $rows;
        }

        $jsonStr = json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($outputPath === null || $outputPath === 'php://output') {
            echo $jsonStr;
        } else {
            file_put_contents($outputPath, $jsonStr);
        }

        return true;
    }

    /**
     * Create an archive backup file on the server.
     */
    public static function createServerBackup($format = 'sql') {
        $dir = self::getBackupDirectory();
        $filename = 'chipipie_backup_' . date('Ymd_His') . '.' . ($format === 'json' ? 'json' : 'sql');
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        if ($format === 'json') {
            self::exportJson($fullPath);
        } else {
            self::exportSql($fullPath);
        }

        return [
            'filename'       => $filename,
            'filepath'       => $fullPath,
            'size'           => filesize($fullPath),
            'size_formatted' => self::formatBytes(filesize($fullPath)),
            'created_at'     => date('Y-m-d H:i:s')
        ];
    }

    /**
     * List all available backups in the backups directory
     */
    public static function listBackups() {
        $dir = self::getBackupDirectory();
        $files = scandir($dir);
        $backups = [];

        foreach ($files as $f) {
            if ($f === '.' || $f === '..' || $f === '.htaccess' || $f === 'index.php') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $f;
            if (!is_file($path)) continue;

            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['sql', 'json', 'gz'])) continue;

            $backups[] = [
                'filename'       => $f,
                'size'           => filesize($path),
                'size_formatted' => self::formatBytes(filesize($path)),
                'created_at'     => date('Y-m-d H:i:s', filemtime($path)),
                'timestamp'      => filemtime($path),
                'type'           => $ext
            ];
        }

        // Sort by newest first
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $backups;
    }

    /**
     * Delete a server backup file securely
     */
    public static function deleteBackup($filename) {
        $sanitized = basename($filename);
        $dir = self::getBackupDirectory();
        $path = $dir . DIRECTORY_SEPARATOR . $sanitized;

        if (file_exists($path) && is_file($path)) {
            return @unlink($path);
        }
        return false;
    }

    /**
     * Restore database from SQL file path or SQL raw string
     */
    public static function restoreSql($input) {
        @set_time_limit(300); // Allow up to 5 minutes for restoration
        @ini_set('memory_limit', '512M');

        $db = getDB();
        $sql = '';

        if (is_file($input)) {
            $sql = file_get_contents($input);
        } else {
            $sql = (string)$input;
        }

        if (empty(trim($sql))) {
            throw new Exception("Restore file is empty.");
        }

        $startTime = microtime(true);

        // Turn off foreign keys and autocommit for speed and integrity
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");
        $db->exec("SET NAMES utf8mb4;");

        // Split SQL statements safely
        $statements = self::splitSqlStatements($sql);
        $executed = 0;
        $errors = [];

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') continue;

            try {
                $db->exec($stmt);
                $executed++;
            } catch (\Throwable $e) {
                // Log non-fatal or fatal error
                $errors[] = [
                    'query' => substr($stmt, 0, 100) . '...',
                    'error' => $e->getMessage()
                ];
            }
        }

        // Re-enable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $duration = round(microtime(true) - $startTime, 2);

        return [
            'success'             => empty($errors) || $executed > 0,
            'statements_executed' => $executed,
            'duration_seconds'    => $duration,
            'errors'              => $errors
        ];
    }

    /**
     * Restore database from JSON backup file or string
     */
    public static function restoreJson($input) {
        @set_time_limit(300);
        $db = getDB();

        $raw = is_file($input) ? file_get_contents($input) : (string)$input;
        $data = json_decode($raw, true);

        if (!$data || !isset($data['tables']) || !is_array($data['tables'])) {
            throw new Exception("Invalid JSON backup structure.");
        }

        $startTime = microtime(true);
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

        $tablesRestored = 0;
        $rowsRestored = 0;

        foreach ($data['tables'] as $tableName => $rows) {
            $cleanTable = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
            
            // Clear current records
            $db->exec("DELETE FROM `{$cleanTable}`");

            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $colSql = '`' . implode('`, `', $columns) . '`';
                $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
                
                $batchSize = 50;
                for ($i = 0; $i < count($rows); $i += $batchSize) {
                    $batch = array_slice($rows, $i, $batchSize);
                    $valuesSql = [];
                    $flatParams = [];

                    foreach ($batch as $r) {
                        $valuesSql[] = $placeholders;
                        foreach ($columns as $c) {
                            $flatParams[] = $r[$c] ?? null;
                        }
                    }

                    $insertQuery = "INSERT INTO `{$cleanTable}` ({$colSql}) VALUES " . implode(', ', $valuesSql);
                    $stmt = $db->prepare($insertQuery);
                    $stmt->execute($flatParams);
                    $rowsRestored += count($batch);
                }
            }
            $tablesRestored++;
        }

        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        $duration = round(microtime(true) - $startTime, 2);

        return [
            'success'         => true,
            'tables_restored' => $tablesRestored,
            'rows_restored'   => $rowsRestored,
            'duration_seconds'=> $duration
        ];
    }

    /**
     * High-speed, robust SQL splitter that respects quoted strings and comments.
     */
    private static function splitSqlStatements($sql) {
        $statements = [];
        $length = strlen($sql);
        $current = '';
        $inString = false;
        $stringChar = '';
        $inComment = false;
        $commentType = '';

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $nextChar = ($i + 1 < $length) ? $sql[$i + 1] : '';

            // Handle string boundaries
            if ($inString) {
                $current .= $char;
                if ($char === '\\') {
                    // Escaped char, grab next
                    if ($i + 1 < $length) {
                        $current .= $sql[++$i];
                    }
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
                continue;
            }

            // Handle comments
            if ($inComment) {
                $current .= $char;
                if ($commentType === 'single' && ($char === "\n" || $char === "\r")) {
                    $inComment = false;
                } elseif ($commentType === 'multi' && $char === '*' && $nextChar === '/') {
                    $current .= '/';
                    $i++;
                    $inComment = false;
                }
                continue;
            }

            // Start of strings
            if ($char === "'" || $char === '"' || $char === '`') {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
                continue;
            }

            // Start of comments
            if ($char === '-' && $nextChar === '-') {
                $inComment = true;
                $commentType = 'single';
                $current .= '--';
                $i++;
                continue;
            }
            if ($char === '#' && ($i === 0 || $sql[$i - 1] === "\n")) {
                $inComment = true;
                $commentType = 'single';
                $current .= '#';
                continue;
            }
            if ($char === '/' && $nextChar === '*') {
                // Check if conditional comment like /*!40101 ... */
                if ($i + 2 < $length && $sql[$i + 2] === '!') {
                    // Conditional statement: treat as executable SQL code!
                    $current .= '/*!';
                    $i += 2;
                    continue;
                }
                $inComment = true;
                $commentType = 'multi';
                $current .= '/*';
                $i++;
                continue;
            }
            if ($char === '*' && $nextChar === '/') {
                $current .= '*/';
                $i++;
                continue;
            }

            // End of SQL statement
            if ($char === ';') {
                $trimStmt = trim($current);
                if ($trimStmt !== '') {
                    $statements[] = $trimStmt;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $remaining = trim($current);
        if ($remaining !== '') {
            $statements[] = $remaining;
        }

        return $statements;
    }

    /**
     * Format byte sizes human-readably
     */
    public static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
