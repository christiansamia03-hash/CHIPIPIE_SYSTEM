<?php
require_once 'api/db_config.php';
$db = getDB();

$settings = $db->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
print_r($settings);
