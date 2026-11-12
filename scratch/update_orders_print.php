<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/api/orders.php';
$src  = file_get_contents($file);
$s    = str_replace("\r\n", "\n", $src);

$oldHandlePrint = <<<'OLDPRINT'
function handlePrintSummary() {
    requireLogin();
    $db = getDB();
    $dateFilter = $_GET['date'] ?? 'today';

    // Period label
    $label = match($dateFilter) {
        'today' => 'Today — ' . date('F d, Y'),
        'week'  => 'This Week (' . date('M d', strtotime('monday this week')) . ' – ' . date('M d, Y', strtotime('sunday this week')) . ')',
        'month' => 'This Month — ' . date('F Y'),
        'year'  => 'This Year — ' . date('Y'),
        default => 'All Time'
    };

    $where = match($dateFilter) {
        'today' => "WHERE DATE(o.created_at) = CURDATE()",
        'week'  => "WHERE YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)",
        'month' => "WHERE YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())",
        'year'  => "WHERE YEAR(o.created_at) = YEAR(CURDATE())",
        default => ''
    };
OLDPRINT;

$newHandlePrint = <<<'NEWPRINT'
function handlePrintSummary() {
    requireLogin();
    $db = getDB();
    $dateFilter = $_GET['date'] ?? 'today';
    $dateMode   = trim($_GET['date_mode'] ?? '');
    $dateVal    = trim($_GET['date_val']  ?? '');

    $where  = '';
    $params = [];
    $label  = '';

    if ($dateMode === 'date' && $dateVal) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateVal)) {
            $where    = "WHERE DATE(o.created_at) = ?";
            $params[] = $dateVal;
            $label    = 'Date — ' . date('F d, Y', strtotime($dateVal));
        }
    } elseif ($dateMode === 'week' && $dateVal) {
        if (preg_match('/^(\d{4})-W(\d{1,2})$/', $dateVal, $m)) {
            $dto = new DateTime();
            $dto->setISODate(intval($m[1]), intval($m[2]));
            $start    = $dto->format('Y-m-d 00:00:00');
            $dto->modify('+6 days');
            $end      = $dto->format('Y-m-d 23:59:59');
            $where    = "WHERE o.created_at BETWEEN ? AND ?";
            $params[] = $start;
            $params[] = $end;
            $label    = "Week {$m[2]}, {$m[1]} (" . date('M d', strtotime($start)) . ' – ' . date('M d, Y', strtotime($end)) . ')';
        }
    } elseif ($dateMode === 'month' && $dateVal) {
        if (preg_match('/^(\d{4})-(\d{2})$/', $dateVal)) {
            $start    = $dateVal . "-01 00:00:00";
            $end      = date("Y-m-t 23:59:59", strtotime($start));
            $where    = "WHERE o.created_at BETWEEN ? AND ?";
            $params[] = $start;
            $params[] = $end;
            $label    = date('F Y', strtotime($start));
        }
    } elseif ($dateMode === 'year' && $dateVal) {
        $year = intval($dateVal);
        if ($year >= 2000 && $year <= 2100) {
            $where    = "WHERE YEAR(o.created_at) = ?";
            $params[] = $year;
            $label    = "Year {$year}";
        }
    }

    if (!$where) {
        $label = match($dateFilter) {
            'today' => 'Today — ' . date('F d, Y'),
            'week'  => 'This Week (' . date('M d', strtotime('monday this week')) . ' – ' . date('M d, Y', strtotime('sunday this week')) . ')',
            'month' => 'This Month — ' . date('F Y'),
            'year'  => 'This Year — ' . date('Y'),
            default => 'All Time'
        };

        $where = match($dateFilter) {
            'today' => "WHERE DATE(o.created_at) = CURDATE()",
            'week'  => "WHERE YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)",
            'month' => "WHERE YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())",
            'year'  => "WHERE YEAR(o.created_at) = YEAR(CURDATE())",
            default => ''
        };
    }
NEWPRINT;

if (strpos($s, $oldHandlePrint) !== false) {
    $s = str_replace($oldHandlePrint, $newHandlePrint, $s);
    echo "Replaced oldHandlePrint\n";
} else {
    echo "ERROR: oldHandlePrint not found\n";
    exit(1);
}

// Update the 4 queries in handlePrintSummary to use prepare/execute when $params is not empty
$oldQueries = <<<'QUERIESOLD'
    // Order totals
    $statusFilter = $where ? "AND o.status = 'Completed'" : "WHERE o.status = 'Completed'";
    $summary = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders o $where $statusFilter")->fetch();

    // Payment method breakdown
    $payments = $db->query("
        SELECT o.payment_method, COUNT(*) as cnt, COALESCE(SUM(o.total),0) as rev
        FROM orders o $where $statusFilter
        GROUP BY o.payment_method
    ")->fetchAll();

    // Top selling items
    $topItems = $db->query("
        SELECT m.name, SUM(oi.quantity) as qty, COALESCE(SUM(oi.subtotal),0) as rev
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        $where $statusFilter
        GROUP BY m.id
        ORDER BY qty DESC
        LIMIT 10
    ")->fetchAll();

    // Orders list
    $orders = $db->query("
        SELECT o.order_number, o.total, o.payment_method, o.status,
               o.created_at, u.full_name AS cashier,
               GROUP_CONCAT(
                 CONCAT(
                   oi.quantity, '× ', m.name,
                   IF(oi.size IS NOT NULL AND oi.size != '', CONCAT(' (', oi.size, ')'), '')
                 ) SEPARATOR ', '
               ) AS items
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN menu_items m ON oi.menu_item_id = m.id
        $where
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 500
    ")->fetchAll();
QUERIESOLD;

$newQueries = <<<'QUERIESNEW'
    // Order totals
    $statusFilter = $where ? "AND o.status = 'Completed'" : "WHERE o.status = 'Completed'";
    $stmt1 = $db->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as rev FROM orders o $where $statusFilter");
    $stmt1->execute($params);
    $summary = $stmt1->fetch();

    // Payment method breakdown
    $stmt2 = $db->prepare("
        SELECT o.payment_method, COUNT(*) as cnt, COALESCE(SUM(o.total),0) as rev
        FROM orders o $where $statusFilter
        GROUP BY o.payment_method
    ");
    $stmt2->execute($params);
    $payments = $stmt2->fetchAll();

    // Top selling items
    $stmt3 = $db->prepare("
        SELECT m.name, SUM(oi.quantity) as qty, COALESCE(SUM(oi.subtotal),0) as rev
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        $where $statusFilter
        GROUP BY m.id
        ORDER BY qty DESC
        LIMIT 10
    ");
    $stmt3->execute($params);
    $topItems = $stmt3->fetchAll();

    // Orders list
    $stmt4 = $db->prepare("
        SELECT o.order_number, o.total, o.payment_method, o.status,
               o.created_at, u.full_name AS cashier,
               GROUP_CONCAT(
                 CONCAT(
                   oi.quantity, '× ', m.name,
                   IF(oi.size IS NOT NULL AND oi.size != '', CONCAT(' (', oi.size, ')'), '')
                 ) SEPARATOR ', '
               ) AS items
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN menu_items m ON oi.menu_item_id = m.id
        $where
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 500
    ");
    $stmt4->execute($params);
    $orders = $stmt4->fetchAll();
QUERIESNEW;

if (strpos($s, $oldQueries) !== false) {
    $s = str_replace($oldQueries, $newQueries, $s);
    echo "Replaced oldQueries with prepared statements\n";
} else {
    echo "ERROR: oldQueries not found\n";
    exit(1);
}

$out = str_replace("\n", "\r\n", $s);
file_put_contents($file, $out);
echo "Updated orders.php successfully.\n";
