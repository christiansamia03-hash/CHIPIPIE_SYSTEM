<?php
require_once 'api/db_config.php';
$db = getDB();

$rows = $db->query("SELECT * FROM rate_limits")->fetchAll(PDO::FETCH_ASSOC);
echo "Current DB time: " . $db->query("SELECT NOW()")->fetchColumn() . "\n";
echo "Rate limits rows:\n";
print_r($rows);
