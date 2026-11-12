<?php
require_once 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/api/db_config.php';
$db = getDB();

$ids = [17, 18, 19, 20];
$names = ['Pies', 'Drinks', 'Snacks', 'Add-ons'];

foreach ($ids as $id) {
    // Check menu_items
    $s1 = $db->prepare("SELECT count(*) FROM menu_items WHERE category_id = ?");
    $s1->execute([$id]);
    echo "category_id $id in menu_items: " . $s1->fetchColumn() . "\n";
}

// Check other tables
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $cols = $db->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('category_id', $cols)) {
        echo "Table `$t` has category_id column\n";
    }
}
