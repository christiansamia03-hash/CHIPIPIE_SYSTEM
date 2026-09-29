<?php
/**
 * ChipiPie Cafe — Menu / Inventory API
 * 
 * GET               — Fetch all menu items
 * POST ?action=add  — Add menu item
 * POST ?action=update — Update item
 * POST ?action=delete — Delete item (requires token)
 * POST ?action=adjust_stock — Adjust stock
 * POST ?action=set_stock — Set stock directly
 */
require_once __DIR__ . '/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && !$action) { handleFetchMenu(); }
else {
    switch ($action) {
        case 'add':          handleAddItem(); break;
        case 'update':       handleUpdateItem(); break;
        case 'delete':       handleDeleteItem(); break;
        case 'adjust_stock': handleAdjustStock(); break;
        case 'set_stock':    handleSetStock(); break;
        case 'toggle':       handleToggleAvail(); break;
        default: jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
    }
}

function handleFetchMenu() {
    $db = getDB();
    $stmt = $db->query("
        SELECT m.id, m.name, c.name AS category, m.price, m.size_prices, m.emoji, m.image,
               m.stock, m.min_stock, m.is_available, m.category_id
        FROM menu_items m
        JOIN categories c ON m.category_id = c.id
        ORDER BY c.id, m.name
    ");
    $items = $stmt->fetchAll();

    $cats = $db->query("SELECT id, name FROM categories ORDER BY id")->fetchAll();

    jsonResponse(['success' => true, 'items' => $items, 'categories' => $cats]);
}

function handleAddItem() {
    requireAdmin();
    $input = getJsonInput();
    $name = trim($input['name'] ?? '');
    $categoryId = intval($input['category_id'] ?? 0);
    $price = floatval($input['price'] ?? 0);
    $image = $input['image'] ?? null;
    $stock = intval($input['stock'] ?? 0);
    $minStock = intval($input['min_stock'] ?? 5);
    $sizePrices = $input['size_prices'] ?? null;

    if (!$name || !$categoryId || $price <= 0) {
        jsonResponse(['success' => false, 'message' => 'Name, category, and valid price required.'], 400);
    }

    // Validate and encode size_prices if provided
    $sizePricesJson = null;
    if ($sizePrices && is_array($sizePrices) && count($sizePrices) > 0) {
        $sizePricesJson = json_encode($sizePrices);
    } elseif ($sizePrices && is_string($sizePrices)) {
        // Validate it's valid JSON
        $decoded = json_decode($sizePrices, true);
        if ($decoded) $sizePricesJson = $sizePrices;
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO menu_items (name, category_id, price, size_prices, image, stock, min_stock, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $categoryId, $price, $sizePricesJson, $image, $stock, $minStock, $stock > 0 ? 1 : 0]);

    jsonResponse(['success' => true, 'message' => "'$name' added to menu.", 'id' => $db->lastInsertId()]);
}

function handleUpdateItem() {
    requireAdmin();
    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Item ID required.'], 400);

    $db = getDB();
    $fields = []; $params = [];

    foreach (['name', 'emoji', 'image'] as $f) {
        if (isset($input[$f])) { $fields[] = "$f = ?"; $params[] = $input[$f]; }
    }
    if (isset($input['category_id'])) { $fields[] = "category_id = ?"; $params[] = intval($input['category_id']); }
    if (isset($input['price'])) { $fields[] = "price = ?"; $params[] = floatval($input['price']); }
    if (isset($input['stock'])) { $fields[] = "stock = ?"; $params[] = intval($input['stock']); }
    if (isset($input['min_stock'])) { $fields[] = "min_stock = ?"; $params[] = intval($input['min_stock']); }
    if (isset($input['is_available'])) { $fields[] = "is_available = ?"; $params[] = intval($input['is_available']); }
    if (array_key_exists('size_prices', $input)) {
        $sp = $input['size_prices'];
        if ($sp && is_array($sp)) {
            $fields[] = "size_prices = ?"; $params[] = json_encode($sp);
        } elseif ($sp && is_string($sp)) {
            $fields[] = "size_prices = ?"; $params[] = $sp;
        } else {
            $fields[] = "size_prices = ?"; $params[] = null;
        }
    }

    if (empty($fields)) jsonResponse(['success' => false, 'message' => 'No fields to update.'], 400);

    $params[] = $id;
    $db->prepare("UPDATE menu_items SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

    jsonResponse(['success' => true, 'message' => 'Item updated.']);
}

function handleDeleteItem() {
    requireAdmin();
    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    $token = strtoupper(trim($input['token'] ?? ''));

    if (!$id) jsonResponse(['success' => false, 'message' => 'Item ID required.'], 400);
    if (!$token) jsonResponse(['success' => false, 'message' => 'Admin bypass token required to delete items.'], 400);

    // Validate token using PHP time (avoids MySQL timezone mismatch)
    $db = getDB();
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("SELECT * FROM admin_tokens WHERE token = ? AND is_used = 0 AND expires_at > ?");
    $stmt->execute([$token, $now]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow) {
        // Check why it failed for better error message
        $stmt2 = $db->prepare("SELECT * FROM admin_tokens WHERE token = ?");
        $stmt2->execute([$token]);
        $ex = $stmt2->fetch();
        if ($ex) {
            $msg = $ex['is_used'] ? 'This token was already used.' : 'Token expired (expired: ' . $ex['expires_at'] . '). Request a new one.';
        } else {
            $msg = 'Invalid token. Make sure you entered it correctly.';
        }
        jsonResponse(['success' => false, 'message' => $msg], 403);
    }

    // Use the token
    $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = ? WHERE id = ?")->execute([$now, $tokenRow['id']]);

    // Delete the item
    $stmt = $db->prepare("SELECT name FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) jsonResponse(['success' => false, 'message' => 'Item not found.'], 404);

    $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);

    jsonResponse(['success' => true, 'message' => "'{$item['name']}' deleted from menu."]);
}

function handleAdjustStock() {
    requireLogin();
    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    $delta = intval($input['delta'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Item ID required.'], 400);

    $db = getDB();
    $stmt = $db->prepare("SELECT stock, name FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) jsonResponse(['success' => false, 'message' => 'Item not found.'], 404);

    $newStock = max(0, $item['stock'] + $delta);
    $avail = $newStock > 0 ? 1 : 0;

    $db->prepare("UPDATE menu_items SET stock = ?, is_available = ? WHERE id = ?")->execute([$newStock, $avail, $id]);

    jsonResponse(['success' => true, 'message' => "{$item['name']} stock: $newStock", 'stock' => $newStock, 'is_available' => $avail]);
}

function handleSetStock() {
    requireLogin();
    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    $stock = intval($input['stock'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Item ID required.'], 400);
    if ($stock < 0) $stock = 0;

    $db = getDB();
    $avail = $stock > 0 ? 1 : 0;
    $db->prepare("UPDATE menu_items SET stock = ?, is_available = ? WHERE id = ?")->execute([$stock, $avail, $id]);

    jsonResponse(['success' => true, 'message' => 'Stock updated.', 'stock' => $stock, 'is_available' => $avail]);
}

function handleToggleAvail() {
    requireAdmin();
    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Item ID required.'], 400);

    $db = getDB();
    $stmt = $db->prepare("SELECT is_available, name FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) jsonResponse(['success' => false, 'message' => 'Item not found.'], 404);

    $newVal = $item['is_available'] ? 0 : 1;
    $db->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?")->execute([$newVal, $id]);

    $status = $newVal ? 'available' : 'unavailable';
    jsonResponse(['success' => true, 'message' => "{$item['name']} marked $status.", 'is_available' => $newVal]);
}
