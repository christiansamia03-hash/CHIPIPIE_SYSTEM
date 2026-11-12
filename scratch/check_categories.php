<?php
require_once 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/api/db_config.php';
$db = getDB();

echo "=== CATEGORIES ===\n";
$stmt = $db->query("SELECT id, name FROM categories ORDER BY id ASC");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Check how many items belong to this category
    $cntStmt = $db->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
    $cntStmt->execute([$r['id']]);
    $itemCount = $cntStmt->fetchColumn();
    echo "ID {$r['id']}: '{$r['name']}' (Items: $itemCount)\n";
}

echo "\n=== MENU ITEMS CHECK FOR 'Pies', 'Drinks', 'Snack', 'Add-ons' ===\n";
$stmt2 = $db->query("SELECT m.id, m.name, c.name as cat_name FROM menu_items m LEFT JOIN categories c ON m.category_id = c.id WHERE c.name IN ('Pies', 'Drinks', 'Snacks', 'Snack', 'Add-ons', 'Add ons') OR m.name LIKE '%pie%'");
while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "Item ID {$r['id']}: {$r['name']} [Category: {$r['cat_name']}]\n";
}
