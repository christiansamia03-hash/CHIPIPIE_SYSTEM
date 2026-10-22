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
elseif ($action === 'decide_menu_change') { handleDecideMenuChange(); }
else {
    switch ($action) {
        case 'add':                    handleAddItem(); break;
        case 'request_add':            handleRequestAddMenu(); break;
        case 'delete':                 handleDeleteItem(); break;
        case 'request_delete':         handleRequestDeleteMenu(); break;
        case 'cancel_delete':          handleCancelDeleteMenu(); break;
        case 'check_request_status':   handleCheckMenuRequestStatus(); break;
        case 'execute_add_with_code':  handleExecuteAddWithCode(); break;
        case 'update':                 handleUpdateItem(); break;
        case 'adjust_stock':           handleAdjustStock(); break;
        case 'set_stock':              handleSetStock(); break;
        case 'toggle':                 handleToggleAvail(); break;
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

    // Collect item IDs that have pending removal requests
    $pendingDeletes = [];
    try {
        $pStmt = $db->query("SELECT payload FROM system_requests WHERE type = 'menu_change' AND status = 'pending'");
        while ($pRow = $pStmt->fetch()) {
            $pl = json_decode($pRow['payload'], true);
            if ($pl && ($pl['action'] ?? '') === 'delete' && !empty($pl['item_id'])) {
                $pendingDeletes[] = intval($pl['item_id']);
            }
        }
    } catch (Exception $e) {}

    jsonResponse([
        'success'           => true,
        'items'             => $items,
        'categories'        => $cats,
        'pending_deletions' => array_values(array_unique($pendingDeletes))
    ]);
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
    requireLogin();
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

    $isSuperAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
    if (!$isSuperAdmin) {
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
    } else {
        $db = getDB();
    }

    // Delete the item
    $stmt = $db->prepare("SELECT name FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) jsonResponse(['success' => false, 'message' => 'Item not found.'], 404);

    try {
        $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);

        // Clean up any pending system_requests for this item
        $now = date('Y-m-d H:i:s');
        $db->prepare("UPDATE system_requests SET status = 'approved', reviewed_by = ?, reviewed_at = ?, review_note = 'Deleted from menu' WHERE type = 'menu_change' AND status = 'pending' AND (payload LIKE ? OR payload LIKE ?)")
           ->execute([$_SESSION['user_id'] ?? 0, $now, '%"item_id":' . $id . '%', '%"item_id":"' . $id . '"%']);

        // System audit log
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $actorId = $_SESSION['user_id'] ?? null;
            $actorUser = $_SESSION['username'] ?? 'system';
            $db->prepare("INSERT INTO system_logs (actor_id, actor_username, action, target_type, target_id, details, ip_address) VALUES (?, ?, 'menu_item_deleted', 'menu_item', ?, ?, ?)")
               ->execute([$actorId, $actorUser, $id, "Permanently deleted menu item '{$item['name']}' (ID: {$id})", $ip]);
        } catch (Exception $logEx) {}

        jsonResponse(['success' => true, 'message' => "'{$item['name']}' deleted from menu."]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Database error while deleting item: ' . $e->getMessage()], 500);
    }
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
    requireLogin();
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

function handleRequestAddMenu() {
    requireLogin();
    $db = getDB();
    $userId = $_SESSION['user_id'] ?? null;
    $requester = $_SESSION['full_name'] ?? $_SESSION['username'] ?? '';
    $userRole = $_SESSION['role'] ?? 'cashier';
    if ($userId && (!$requester || $requester === 'Staff')) {
        $uStmt = $db->prepare("SELECT full_name, username, role FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $uRow = $uStmt->fetch();
        if ($uRow) {
            $requester = $uRow['full_name'] ?: $uRow['username'];
            $userRole = $uRow['role'];
        }
    }
    if (!$requester) $requester = 'Cashier';
    session_write_close();
    $input = getJsonInput();
    $name = trim($input['name'] ?? '');
    $categoryId = intval($input['category_id'] ?? 1);
    $categoryName = trim($input['category_name'] ?? '');
    $price = floatval($input['price'] ?? 0);
    $image = $input['image'] ?? null;
    $stock = intval($input['stock'] ?? 0);
    $minStock = intval($input['min_stock'] ?? 5);
    $sizePrices = $input['size_prices'] ?? null;

    if (!$name || !$categoryId || $price <= 0) {
        jsonResponse(['success' => false, 'message' => 'Item name, category, and valid price are required.'], 400);
    }

    $db = getDB();

    // Prevent duplicate: check if item already exists in menu
    $checkMenu = $db->prepare("SELECT id FROM menu_items WHERE LOWER(name) = LOWER(?)");
    $checkMenu->execute([$name]);
    if ($checkMenu->fetch()) {
        jsonResponse(['success' => false, 'message' => "A menu item named '{$name}' already exists in the menu."], 409);
    }

    // Prevent duplicate: check if an add request for this item is already pending
    $checkAddPending = $db->prepare("
        SELECT id, requester_name, created_at 
        FROM system_requests 
        WHERE type = 'menu_change' 
          AND status = 'pending' 
          AND (payload LIKE ? OR payload LIKE ?)
        LIMIT 1
    ");
    $checkAddPending->execute([
        '%"name":"' . $name . '"%',
        '%"name": "' . $name . '"%'
    ]);
    $alreadyAdd = $checkAddPending->fetch();
    if ($alreadyAdd) {
        $dateStr = date('M j, Y g:i A', strtotime($alreadyAdd['created_at']));
        jsonResponse([
            'success'         => false,
            'message'         => "An add request for '{$name}' is already pending approval (submitted on {$dateStr}). Duplicate requests are not allowed.",
            'already_pending' => true
        ], 409);
    }

    if (!$categoryName) {
        $catStmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
        $catStmt->execute([$categoryId]);
        $cat = $catStmt->fetch();
        $categoryName = $cat ? $cat['name'] : 'Category #' . $categoryId;
    }

    $pinCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $approvalToken = bin2hex(random_bytes(32));

    // Store PIN code in admin_tokens for POS authorization
    $expiryMin = intval(getSetting('token_expiry_minutes') ?: TOKEN_EXPIRY_MINUTES);
    if ($expiryMin < 5) $expiryMin = 15;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));
    $stmt = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$pinCode, $userId, $approvalToken, $expiresAt]);

    $payload = [
        'action'        => 'add',
        'name'          => $name,
        'category_id'   => $categoryId,
        'category_name' => $categoryName,
        'price'         => $price,
        'stock'         => $stock,
        'min_stock'     => $minStock,
        'image'         => $image,
        'size_prices'   => $sizePrices,
        'pin_code'      => $pinCode,
    ];

    $title = "Add Menu Item: {$name}";
    $desc = "Category: {$categoryName} | Price: ₱" . number_format($price, 2) . " | Initial Stock: {$stock} | Code: {$pinCode}";

    $stmt = $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload, approval_token, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute(['menu_change', $userId, $requester, $title, $desc, json_encode($payload), $approvalToken]);

    sendMenuRequestEmail('add', $payload, $requester, $approvalToken, $userRole);

    $isCashier = ($userRole === 'cashier');

    $resp = [
        'success'        => true,
        'message'        => $isCashier
            ? "Request to add '{$name}' submitted for approval."
            : "Request to add '{$name}' submitted. Authorization code sent to Admin & Super Admin email.",
        'approval_token' => $approvalToken,
        'is_cashier'     => $isCashier
    ];
    if (DEV_MODE && !$isCashier) {
        $resp['dev_token'] = $pinCode;
    }
    jsonResponse($resp);
}

function handleRequestDeleteMenu() {
    requireLogin();
    $db = getDB();
    $userId = $_SESSION['user_id'] ?? null;
    $requester = $_SESSION['full_name'] ?? $_SESSION['username'] ?? '';
    $userRole = $_SESSION['role'] ?? 'cashier';
    if ($userId && (!$requester || $requester === 'Staff')) {
        $uStmt = $db->prepare("SELECT full_name, username, role FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $uRow = $uStmt->fetch();
        if ($uRow) {
            $requester = $uRow['full_name'] ?: $uRow['username'];
            $userRole = $uRow['role'];
        }
    }
    if (!$requester) $requester = 'Staff';
    session_write_close();
    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    $reason = trim($input['reason'] ?? 'Requested from POS');

    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Item ID is required.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.id, m.name, c.name AS category, m.price, m.stock, m.image 
        FROM menu_items m 
        LEFT JOIN categories c ON m.category_id = c.id 
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        jsonResponse(['success' => false, 'message' => 'Menu item not found.'], 404);
    }

    // Prevent duplicate: check if a deletion request is already pending for this item
    $checkDelPending = $db->prepare("
        SELECT id, requester_name, created_at 
        FROM system_requests 
        WHERE type = 'menu_change' 
          AND status = 'pending' 
          AND (payload LIKE ? OR payload LIKE ?)
        LIMIT 1
    ");
    $checkDelPending->execute([
        '%"item_id":' . $id . '%',
        '%"item_id":"' . $id . '"%'
    ]);
    $alreadyDel = $checkDelPending->fetch();
    if ($alreadyDel) {
        $dateStr = date('M j, Y g:i A', strtotime($alreadyDel['created_at']));
        $byStr = $alreadyDel['requester_name'] ? " by {$alreadyDel['requester_name']}" : '';
        jsonResponse([
            'success'         => false,
            'message'         => "A removal request for '{$item['name']}' has already been submitted{$byStr} on {$dateStr} and is currently pending approval. Duplicate requests are not allowed.",
            'already_pending' => true,
            'request_id'      => $alreadyDel['id']
        ], 409);
    }

    $pinCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $approvalToken = bin2hex(random_bytes(32));

    // Store PIN code in admin_tokens for POS authorization
    $expiryMin = intval(getSetting('token_expiry_minutes') ?: TOKEN_EXPIRY_MINUTES);
    if ($expiryMin < 5) $expiryMin = 15;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMin minutes"));
    $stmt = $db->prepare("INSERT INTO admin_tokens (token, user_id, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$pinCode, $userId, $approvalToken, $expiresAt]);

    $payload = [
        'action'   => 'delete',
        'item_id'  => $id,
        'name'     => $item['name'],
        'category' => $item['category'] ?? 'General',
        'price'    => $item['price'],
        'stock'    => $item['stock'],
        'reason'   => $reason,
        'pin_code' => $pinCode
    ];

    $title = "Remove Menu Item: {$item['name']}";
    $desc = "Category: {$item['category']} | Price: ₱" . number_format($item['price'], 2) . " | Stock: {$item['stock']} | Code: {$pinCode}";

    $stmt = $db->prepare("INSERT INTO system_requests (type, requested_by, requester_name, title, description, payload, approval_token, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute(['menu_change', $userId, $requester, $title, $desc, json_encode($payload), $approvalToken]);

    sendMenuRequestEmail('delete', $payload, $requester, $approvalToken, $userRole);

    $resp = [
        'success'        => true,
        'message'        => "Request to remove '{$item['name']}' sent to admin email for approval.",
        'approval_token' => $approvalToken
    ];
    if (DEV_MODE) {
        $resp['dev_token'] = $pinCode;
    }
    jsonResponse($resp);
}

function handleCancelDeleteMenu() {
    ensureSession();
    $input = getJsonInput();
    $id = intval($input['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Item ID is required.'], 400);
    }

    $db = getDB();
    $checkStmt = $db->prepare("
        SELECT id, requester_name, payload 
        FROM system_requests 
        WHERE type = 'menu_change' 
          AND status = 'pending' 
          AND (payload LIKE ? OR payload LIKE ?)
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $checkStmt->execute([
        '%"item_id":' . $id . '%',
        '%"item_id":"' . $id . '"%'
    ]);
    $req = $checkStmt->fetch();

    if (!$req) {
        jsonResponse(['success' => false, 'message' => 'No pending removal request found for this item.'], 404);
    }

    $now = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'] ?? null;
    $canceller = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Staff';

    // Mark request as rejected/cancelled so it is no longer pending
    $db->prepare("
        UPDATE system_requests 
        SET status = 'rejected', reviewed_by = ?, reviewed_at = ?, review_note = ? 
        WHERE id = ?
    ")->execute([$userId, $now, "Removal request cancelled by {$canceller}", $req['id']]);

    jsonResponse([
        'success' => true,
        'message' => 'Pending removal request has been cancelled.'
    ]);
}

function handleCheckMenuRequestStatus() {
    requireLogin();
    session_write_close();
    $token = trim($_GET['token'] ?? '');
    if (!$token) {
        jsonResponse(['success' => false, 'message' => 'Token required.'], 400);
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT id, type, status, review_note, payload FROM system_requests WHERE approval_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) {
        jsonResponse(['success' => false, 'message' => 'Request not found.'], 404);
    }
    $via = (strpos($row['review_note'] ?? '', 'Email') !== false) ? 'Email Link'
         : ((strpos($row['review_note'] ?? '', 'PIN') !== false || strpos($row['review_note'] ?? '', 'Code') !== false) ? 'Authorization Code' : 'Super Admin');
    jsonResponse([
        'success'        => true,
        'request_status' => $row['status'],
        'approved_via'   => $via,
        'item_name'      => json_decode($row['payload'] ?? '{}', true)['name'] ?? 'Menu Item'
    ]);
}

function handleExecuteAddWithCode() {
    requireLogin();
    session_write_close();
    $input = getJsonInput();
    $token = strtoupper(trim($input['token'] ?? ''));
    if (!$token) jsonResponse(['success' => false, 'message' => 'Authorization code is required.'], 400);

    $db = getDB();
    $now = date('Y-m-d H:i:s');

    // Validate PIN in admin_tokens
    $stmt = $db->prepare("SELECT * FROM admin_tokens WHERE token = ? AND is_used = 0 AND expires_at > ?");
    $stmt->execute([$token, $now]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired authorization code.'], 400);
    }

    $approvalToken = $tokenRow['purpose'];

    // Mark token as used
    $db->prepare("UPDATE admin_tokens SET is_used = 1, used_at = ? WHERE id = ?")->execute([$now, $tokenRow['id']]);

    // Fetch pending request
    $stmt = $db->prepare("SELECT * FROM system_requests WHERE approval_token = ? AND status = 'pending'");
    $stmt->execute([$approvalToken]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['success' => true, 'message' => 'Request already processed.']);
    }

    $payload = $request['payload'] ? json_decode($request['payload'], true) : [];
    $name = trim($payload['name'] ?? '');
    $categoryId = intval($payload['category_id'] ?? 1);
    $price = floatval($payload['price'] ?? 0);
    $image = $payload['image'] ?? null;
    $stock = intval($payload['stock'] ?? 0);
    $minStock = intval($payload['min_stock'] ?? 5);
    $sizePrices = $payload['size_prices'] ?? null;
    $sizePricesJson = null;
    if ($sizePrices && is_array($sizePrices)) {
        $sizePricesJson = json_encode($sizePrices);
    } elseif ($sizePrices && is_string($sizePrices)) {
        $sizePricesJson = $sizePrices;
    }

    if ($name && $categoryId && $price > 0) {
        $insert = $db->prepare("INSERT INTO menu_items (name, category_id, price, size_prices, image, stock, min_stock, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([$name, $categoryId, $price, $sizePricesJson, $image, $stock, $minStock, $stock > 0 ? 1 : 0]);
    }

    $db->prepare("UPDATE system_requests SET status = 'approved', reviewed_at = ?, review_note = 'Processed via Authorization Code' WHERE id = ?")
       ->execute([$now, $request['id']]);

    jsonResponse(['success' => true, 'message' => "'{$name}' added to menu successfully via authorization code."]);
}

function sendMenuRequestEmail($actionType, $payload, $requester, $approvalToken, $userRole = '') {
    $storeName = getSetting('store_name') ?: 'ChipiPie Cafe';
    $ownerEmail = trim(getSetting('owner_email') ?? '');
    $superAdminEmail = trim(getSetting('superadmin_email') ?? '');

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $approveUrl = "http://" . $host . "/CHIPIPIE_SYSTEM/api/menu.php?action=decide_menu_change&token=" . $approvalToken . "&decision=approve";
    $rejectUrl  = "http://" . $host . "/CHIPIPIE_SYSTEM/api/menu.php?action=decide_menu_change&token=" . $approvalToken . "&decision=reject";

    $isAdd = ($actionType === 'add');
    $actionTitle = $isAdd ? 'Add Menu Item Request' : 'Remove Menu Item Request';
    $badgeColor = $isAdd ? '#16a34a' : '#dc2626';
    $badgeText = $isAdd ? '+ ADD ITEM' : '✕ REMOVE ITEM';
    $itemName = htmlspecialchars($payload['name'] ?? 'Item');
    $categoryName = htmlspecialchars($payload['category_name'] ?? $payload['category'] ?? 'General');
    $price = number_format(floatval($payload['price'] ?? 0), 2);
    $stock = intval($payload['stock'] ?? 0);
    $pinCode = htmlspecialchars($payload['pin_code'] ?? '');
    $roleTag = $userRole ? ' (' . ucfirst($userRole) . ')' : '';

    $detailRows = "
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Action:</td><td><span style='background: {$badgeColor}22; color: {$badgeColor}; border: 1px solid {$badgeColor}44; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;'>{$badgeText}</span></td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Item Name:</td><td style='font-weight: 700; color: #fff;'>{$itemName}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Category:</td><td style='color: #ddd;'>{$categoryName}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Price:</td><td style='color: #fbbf24; font-weight: 700;'>₱{$price}</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Stock:</td><td style='color: #ddd;'>{$stock} units</td></tr>
        <tr><td style='padding: 6px 0; font-weight: 600; color: #aaa;'>Requested By:</td><td style='color: #ddd;'>{$requester}{$roleTag}</td></tr>";

    $tokenBlock = "";
    if ($pinCode) {
        $tokenBlock = "
        <div style='text-align:center;margin:20px 0;'>
            <p style='font-size:12px;color:#aaa;margin-bottom:6px;'>Authorization Token / Code for POS:</p>
            <span style='background:#3a2f2d;color:#fff;padding:12px 24px;border-radius:10px;font-size:24px;font-weight:700;letter-spacing:4px;font-family:monospace;'>$pinCode</span>
            <p style='font-size:11px;color:#888;margin-top:6px;'>Use this code in POS to authorize " . ($isAdd ? "adding" : "removing") . " {$itemName}</p>
        </div>";
    }

    $saButtons = "
    <div style='display: flex; gap: 10px; margin-top: 20px; text-align: center;'>
        <a href='{$approveUrl}' style='flex: 1; display: inline-block; background: #16a34a; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10003; " . ($isAdd ? 'Approve Add' : 'Approve Removal') . "</a>
        <a href='{$rejectUrl}' style='flex: 1; display: inline-block; background: #3f3f46; color: #fff; padding: 12px 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;'>&#10007; Reject</a>
    </div>";

    $saBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: {$badgeColor}; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>" . ($isAdd ? '☕' : '🗑️') . "</div>
            <h2 style='color: #fff; margin: 10px 0 5px;'>{$storeName}</h2>
            <p style='color: #aaa; font-size: 13px; margin: 0;'>{$actionTitle} &mdash; Super Admin Action Required</p>
        </div>
        <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
            <table style='width: 100%; font-size: 14px; color: #ddd; margin: 0 0 16px 0;'>{$detailRows}</table>
            {$tokenBlock}
            {$saButtons}
            <p style='font-size: 11px; color: #888; text-align: center; margin-top: 16px; margin-bottom: 0;'>Super Admin can click Approve above to immediately approve, or authorize via token in POS.</p>
        </div>
    </div>";

    $adminBody = "
    <div style='font-family: Segoe UI, sans-serif; max-width: 500px; margin: 0 auto; padding: 30px; background: #1a1514; color: #f4f4f4; border-radius: 16px; border: 1px solid #332d2c;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <div style='display: inline-block; width: 50px; height: 50px; background: {$badgeColor}; color: #fff; border-radius: 12px; line-height: 50px; font-size: 24px;'>" . ($isAdd ? '☕' : '🗑️') . "</div>
            <h2 style='color: #fff; margin: 10px 0 5px;'>{$storeName}</h2>
            <p style='color: #aaa; font-size: 13px; margin: 0;'>{$actionTitle} &mdash; Authorization Token</p>
        </div>
        <div style='background: #2a2423; border-radius: 12px; padding: 24px; border: 1px solid #3d3533;'>
            <table style='width: 100%; font-size: 14px; color: #ddd; margin: 0 0 16px 0;'>{$detailRows}</table>
            {$tokenBlock}
            <p style='font-size: 11px; color: #888; text-align: center; margin-top: 16px; margin-bottom: 0;'>Enter the authorization token in the POS modal to confirm this request.</p>
        </div>
    </div>";

    $recipients = [];
    if ($ownerEmail && isValidSendableEmail($ownerEmail)) {
        $recipients[strtolower($ownerEmail)] = ['email' => $ownerEmail, 'is_super' => true];
    }
    if ($superAdminEmail && isValidSendableEmail($superAdminEmail)) {
        $recipients[strtolower($superAdminEmail)] = ['email' => $superAdminEmail, 'is_super' => true];
    }

    try {
        $db = getDB();
        $adminStmt = $db->query("SELECT email, role FROM users WHERE role IN ('admin', 'super_admin') AND is_active = 1");
        while ($row = $adminStmt->fetch()) {
            $e = trim($row['email'] ?? '');
            if ($e && isValidSendableEmail($e)) {
                $isSuper = ($row['role'] === 'super_admin');
                $key = strtolower($e);
                if (!isset($recipients[$key]) || $isSuper) {
                    $recipients[$key] = [
                        'email'    => $e,
                        'is_super' => $isSuper || ($key === strtolower($superAdminEmail))
                    ];
                }
            }
        }
    } catch (Exception $e) {}

    $subject = "{$storeName} — {$actionTitle}: {$itemName}";
    foreach ($recipients as $r) {
        $body = $r['is_super'] ? $saBody : $adminBody;
        sendEmail($r['email'], $subject, $body);
    }
}

function handleDecideMenuChange() {
    $token = $_GET['token'] ?? '';
    $decision = $_GET['decision'] ?? '';

    if (!$token || !in_array($decision, ['approve', 'reject'])) {
        die("Invalid link.");
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM system_requests WHERE approval_token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    $request = $stmt->fetch();

    if (!$request) {
        die("Request not found, already processed, or invalid token.");
    }

    $now = date('Y-m-d H:i:s');
    $status = $decision === 'approve' ? 'approved' : 'rejected';
    $payload = $request['payload'] ? json_decode($request['payload'], true) : [];
    $actionType = $payload['action'] ?? ($payload['item_id'] ? 'delete' : 'add');
    $itemName = htmlspecialchars($payload['name'] ?? 'Menu Item');

    if ($decision === 'approve') {
        if ($actionType === 'add') {
            $name = trim($payload['name'] ?? '');
            $categoryId = intval($payload['category_id'] ?? 1);
            $price = floatval($payload['price'] ?? 0);
            $image = $payload['image'] ?? null;
            $stock = intval($payload['stock'] ?? 0);
            $minStock = intval($payload['min_stock'] ?? 5);
            $sizePrices = $payload['size_prices'] ?? null;
            $sizePricesJson = null;
            if ($sizePrices && is_array($sizePrices)) {
                $sizePricesJson = json_encode($sizePrices);
            } elseif ($sizePrices && is_string($sizePrices)) {
                $sizePricesJson = $sizePrices;
            }

            if ($name && $categoryId && $price > 0) {
                $insert = $db->prepare("INSERT INTO menu_items (name, category_id, price, size_prices, image, stock, min_stock, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->execute([$name, $categoryId, $price, $sizePricesJson, $image, $stock, $minStock, $stock > 0 ? 1 : 0]);
            }
        } elseif ($actionType === 'delete') {
            $itemId = intval($payload['item_id'] ?? $payload['id'] ?? 0);
            if ($itemId) {
                $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$itemId]);
            }
        }
    }

    $db->prepare("UPDATE system_requests SET status = ?, reviewed_at = ?, review_note = 'Processed via Email Link' WHERE id = ?")
       ->execute([$status, $now, $request['id']]);

    $actionText = $decision === 'approve' ? 'Approved' : 'Rejected';
    $color = $decision === 'approve' ? '#16a34a' : '#ef4444';
    $icon = $decision === 'approve' ? '✅' : '❌';
    $verb = $actionType === 'add' ? ($decision === 'approve' ? 'added to' : 'not added to') : ($decision === 'approve' ? 'removed from' : 'kept on');

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Menu Request {$actionText}</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #1a1514; color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .container { text-align: center; background: #2a2423; padding: 40px; border-radius: 16px; border: 1px solid #3d3533; box-shadow: 0 10px 25px rgba(0,0,0,0.5); max-width: 440px; width: 90%; }
            .icon { font-size: 64px; margin-bottom: 20px; }
            h1 { color: {$color}; margin: 0 0 10px 0; font-size: 26px; }
            p { color: #aaa; margin: 0; line-height: 1.5; font-size: 15px; }
            .badge { display: inline-block; background: #332b29; padding: 8px 16px; border-radius: 8px; color: #fff; font-weight: 600; margin: 16px 0; border: 1px solid #4a3e3c; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='icon'>{$icon}</div>
            <h1>Menu Request {$actionText}</h1>
            <div class='badge'>{$itemName}</div>
            <p>The request has been <strong>{$actionText}</strong>. The item has been {$verb} the menu.</p>
            <p style='margin-top: 20px; font-size: 13px; color: #777;'>You can now close this tab.</p>
        </div>
    </body>
    </html>";
    exit;
}
