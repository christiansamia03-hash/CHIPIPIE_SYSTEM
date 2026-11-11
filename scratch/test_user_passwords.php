<?php
require_once 'api/db_config.php';
$db = getDB();

$users = $db->query("SELECT id, username, password, role FROM users")->fetchAll(PDO::FETCH_ASSOC);

$commonPasswords = [
    'admin123', 'Admin123', 'Admin123!', 'admin', 'password', '12345678', 'ChipiPie123!', 'chipipie123',
    'cashier123', 'Cashier123', 'Cashier123!', 'cashier',
    'superadmin123', 'Superadmin123!', 'SuperAdmin123!', 'superadmin', 'SuperAdmin@123', 'SuperAdmin'
];

foreach ($users as $u) {
    echo "User: {$u['username']} (Role: {$u['role']}) -> ";
    $found = false;
    foreach ($commonPasswords as $pw) {
        if (password_verify($pw, $u['password'])) {
            echo "MATCHES: '$pw'\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "No match in common test list. Hash: " . substr($u['password'], 0, 15) . "...\n";
    }
}
