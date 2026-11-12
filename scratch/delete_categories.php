<?php
require_once 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/api/db_config.php';
$db = getDB();

$stmt = $db->prepare("DELETE FROM categories WHERE id IN (17, 18, 19, 20) OR name IN ('Pies', 'Drinks', 'Snacks', 'Add-ons')");
$stmt->execute();
$deleted = $stmt->rowCount();

echo "Deleted categories count: $deleted\n\n";

echo "Remaining categories:\n";
$q = $db->query("SELECT id, name FROM categories ORDER BY id ASC");
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    echo "ID {$r['id']}: '{$r['name']}'\n";
}
