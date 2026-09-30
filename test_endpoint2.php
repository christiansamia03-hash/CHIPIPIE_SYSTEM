<?php
$_GET['action'] = 'stats';
session_start();
$_SESSION['user_id'] = 2; // Cashier or admin
$_SESSION['role'] = 'cashier';
$_SESSION['username'] = 'test';

ob_start();
try {
    require_once 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/api/orders.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();
echo "OUTPUT BEGINS:\n";
echo substr($output, 0, 500); 
echo "\n... [truncated] ...\n";
