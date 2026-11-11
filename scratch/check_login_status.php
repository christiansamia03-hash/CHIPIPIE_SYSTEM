<?php
require_once 'api/db_config.php';
$db = getDB();

echo "=== USERS TABLE ===\n";
$users = $db->query("SELECT id, username, role, is_active, email FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "ID: {$u['id']}, User: {$u['username']}, Role: {$u['role']}, Active: {$u['is_active']}, Email: {$u['email']}\n";
}

echo "\n=== ACTIVE RATE LIMITS / LOCKOUTS ===\n";
try {
    $rls = $db->query("SELECT * FROM rate_limits WHERE locked_until > NOW() OR hits > 0 ORDER BY last_hit_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if ($rls) {
        foreach ($rls as $r) {
            echo "Key: {$r['rate_key']}, Action: {$r['action']}, Identifier: {$r['identifier']}, Hits: {$r['hits']}, LockoutCount: {$r['lockout_count']}, LockedUntil: {$r['locked_until']}\n";
        }
    } else {
        echo "No active rate limits or lockouts.\n";
    }
} catch (Exception $e) {
    echo "Rate limits error: " . $e->getMessage() . "\n";
}

echo "\n=== RECENT SYSTEM LOGS (Last 10) ===\n";
try {
    $logs = $db->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($logs as $l) {
        echo "[{$l['created_at']}] Actor: {$l['actor_username']}, Action: {$l['action']}, Target: {$l['target_type']} #{$l['target_id']}, Details: {$l['details']}\n";
    }
} catch (Exception $e) {
    echo "Logs error: " . $e->getMessage() . "\n";
}
