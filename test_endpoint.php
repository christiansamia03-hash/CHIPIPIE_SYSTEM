<?php
$_GET['action'] = 'dashboard';
// Need to simulate a logged-in super admin to avoid requireSuperAdmin() redirecting/failing
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';
$_SESSION['username'] = 'admin';

ob_start();
try {
    require_once 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/api/superadmin.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();
echo "OUTPUT BEGINS:\n";
echo substr($output, 0, 500); // Check the first 500 chars to see if it's a valid JSON or PHP error.
echo "\n... [truncated] ...\n";
