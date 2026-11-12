<?php
require_once 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/api/db_config.php';
$db = getDB();
$cols = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}
