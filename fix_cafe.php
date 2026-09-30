<?php
require_once 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/api/db_config.php';
$db = getDB();

$stmt = $db->prepare("UPDATE menu_items SET name = REPLACE(name, 'Caf├®', 'Café') WHERE name LIKE '%Caf├®%'");
$stmt->execute();
echo "Updated " . $stmt->rowCount() . " rows.\n";

$items = $db->query("SELECT id, name FROM menu_items WHERE name LIKE '%Caf%'")->fetchAll();
foreach ($items as $item) {
    echo $item['id'] . ": " . $item['name'] . "\n";
}
