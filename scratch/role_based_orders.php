<?php
// 1. Update chipipie-pos.php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);
$s    = str_replace("\r\n", "\n", $src);

// Add cashier today tab to HTML
$targetTabs = <<<'HTMLTABS'
          <div class="d-flex gap-2 flex-wrap" id="order-filter-tabs">
            <button id="ofilter-pick-date"  class="cat-pill active" onclick="setOrderPickerMode('date')"><i class="bi bi-calendar-event"></i> Date</button>
            <button id="ofilter-pick-week"  class="cat-pill" onclick="setOrderPickerMode('week')"><i class="bi bi-calendar-range"></i> Week</button>
            <button id="ofilter-pick-month" class="cat-pill" onclick="setOrderPickerMode('month')"><i class="bi bi-calendar-month"></i> Month</button>
            <button id="ofilter-pick-year"  class="cat-pill" onclick="setOrderPickerMode('year')"><i class="bi bi-calendar"></i> Year</button>
          </div>
HTMLTABS;

$replaceTabs = <<<'HTMLREPLACE'
          <div class="d-flex gap-2 flex-wrap" id="order-filter-tabs">
            <button id="ofilter-pick-date"  class="cat-pill active" onclick="setOrderPickerMode('date')"><i class="bi bi-calendar-event"></i> Date</button>
            <button id="ofilter-pick-week"  class="cat-pill" onclick="setOrderPickerMode('week')"><i class="bi bi-calendar-range"></i> Week</button>
            <button id="ofilter-pick-month" class="cat-pill" onclick="setOrderPickerMode('month')"><i class="bi bi-calendar-month"></i> Month</button>
            <button id="ofilter-pick-year"  class="cat-pill" onclick="setOrderPickerMode('year')"><i class="bi bi-calendar"></i> Year</button>
          </div>
          <!-- Cashier Single Tab (Today only) -->
          <div id="order-cashier-tab" style="display:none;" class="d-flex gap-2 flex-wrap">
            <button class="cat-pill active" style="cursor:default;"><i class="bi bi-clock-history me-1"></i>Today</button>
          </div>
HTMLREPLACE;

if (strpos($s, $targetTabs) !== false) {
    $s = str_replace($targetTabs, $replaceTabs, $s);
    echo "Added cashier tab to HTML\n";
} else {
    echo "ERROR: targetTabs not found\n";
    exit(1);
}

// Global _isUserAdmin declaration
$targetOrdersHead = "    /* ========== ORDERS ========== */\n    let currentOrderFilter = 'today';";
$replaceOrdersHead = "    /* ========== ORDERS ========== */\n    let _isUserAdmin       = false;\n    let currentOrderFilter = 'today';";

if (strpos($s, $targetOrdersHead) !== false) {
    $s = str_replace($targetOrdersHead, $replaceOrdersHead, $s);
    echo "Added _isUserAdmin declaration\n";
} else {
    echo "WARNING: targetOrdersHead not found\n";
}

// setOrderPickerMode permission check
$targetSetPicker = "    function setOrderPickerMode(mode) {\n      _orderPickerMode = mode;";
$replaceSetPicker = "    function setOrderPickerMode(mode) {\n      if (!_isUserAdmin) return;\n      _orderPickerMode = mode;";

if (strpos($s, $targetSetPicker) !== false) {
    $s = str_replace($targetSetPicker, $replaceSetPicker, $s);
    echo "Added permission check to setOrderPickerMode\n";
} else {
    echo "WARNING: targetSetPicker not found\n";
}

// showView('orders') check
$targetShowViewOrders = "      if (name === 'orders') { if (!_orderPickerMode) { setOrderPickerMode('date'); } else { applyOrderPickerFilter(); } }";
$replaceShowViewOrders = <<<'SVREPLACE'
      if (name === 'orders') {
        if (!_isUserAdmin) {
          _orderPickerMode = '';
          currentOrderFilter = 'today';
          const card = document.getElementById('order-picker-card');
          if (card) card.style.display = 'none';
          renderOrders();
        } else {
          if (!_orderPickerMode) { setOrderPickerMode('date'); } else { applyOrderPickerFilter(); }
        }
      }
SVREPLACE;

if (strpos($s, $targetShowViewOrders) !== false) {
    $s = str_replace($targetShowViewOrders, $replaceShowViewOrders, $s);
    echo "Updated showView('orders') for cashier vs admin\n";
} else {
    echo "WARNING: targetShowViewOrders not found\n";
}

// renderOrders role check
$targetRenderOrders = <<<'ROOLD'
      let apiUrl;
      if (_orderPickerMode && _orderPickerVal) {
        apiUrl = `orders.php?date_mode=${_orderPickerMode}&date_val=${encodeURIComponent(_orderPickerVal)}`;
      } else {
        apiUrl = `orders.php?date=${currentOrderFilter}`;
      }
ROOLD;

$replaceRenderOrders = <<<'ROREPLACE'
      let apiUrl;
      if (!_isUserAdmin) {
        apiUrl = 'orders.php?date=today';
      } else if (_orderPickerMode && _orderPickerVal) {
        apiUrl = `orders.php?date_mode=${_orderPickerMode}&date_val=${encodeURIComponent(_orderPickerVal)}`;
      } else {
        apiUrl = `orders.php?date=${currentOrderFilter}`;
      }
ROREPLACE;

if (strpos($s, $targetRenderOrders) !== false) {
    $s = str_replace($targetRenderOrders, $replaceRenderOrders, $s);
    echo "Updated renderOrders() to lock cashiers to today\n";
} else {
    echo "WARNING: targetRenderOrders not found\n";
}

// printOrders role check
$targetPrintOrders = <<<'POOLD'
    /* ========== PRINT ORDERS REPORT ========== */
    async function printOrders() {
      let printUrl = `orders.php?action=print_summary&date=${currentOrderFilter}`;
      let label = 'Today';
      if (_orderPickerMode && _orderPickerVal) {
        printUrl = `orders.php?action=print_summary&date_mode=${_orderPickerMode}&date_val=${encodeURIComponent(_orderPickerVal)}`;
        label = document.getElementById('order-picker-summary')?.textContent?.replace('Showing: ','') || _orderPickerVal;
      }
POOLD;

$replacePrintOrders = <<<'POREPLACE'
    /* ========== PRINT ORDERS REPORT ========== */
    async function printOrders() {
      let printUrl = 'orders.php?action=print_summary&date=today';
      let label = 'Today';
      if (_isUserAdmin && _orderPickerMode && _orderPickerVal) {
        printUrl = `orders.php?action=print_summary&date_mode=${_orderPickerMode}&date_val=${encodeURIComponent(_orderPickerVal)}`;
        label = document.getElementById('order-picker-summary')?.textContent?.replace('Showing: ','') || _orderPickerVal;
      }
POREPLACE;

if (strpos($s, $targetPrintOrders) !== false) {
    $s = str_replace($targetPrintOrders, $replacePrintOrders, $s);
    echo "Updated printOrders() for cashier vs admin\n";
} else {
    echo "WARNING: targetPrintOrders not found\n";
}

// activateApp(isAdmin) update
$targetActivate = <<<'AAOLD'
      if (!isAdmin) {
        mgmtLabel.style.display = 'none';
        menuBtn.style.display = 'flex';
        reportsBtn.style.display = 'none';
        if (usersBtn) usersBtn.style.display = 'none';
        safeBuildCharts(); // Build charts so Dashboard view works for cashiers too
        showView('pos', document.getElementById('nav-pos-btn') || document.querySelectorAll('.nav-item-btn')[1]);
      } else {
        mgmtLabel.style.display = 'block';
        menuBtn.style.display = 'flex';
        reportsBtn.style.display = 'flex';
        if (usersBtn) usersBtn.style.display = 'flex';
        safeBuildCharts();
        showView('dashboard', document.getElementById('nav-dash-btn') || document.querySelectorAll('.nav-item-btn')[0]);
      }
AAOLD;

$replaceActivate = <<<'AAREPLACE'
      _isUserAdmin = !!isAdmin;
      window.posIsAdmin = !!isAdmin;

      const orderFilterTabs = document.getElementById('order-filter-tabs');
      const orderCashierTab = document.getElementById('order-cashier-tab');
      const orderPickerCard = document.getElementById('order-picker-card');

      if (!isAdmin) {
        mgmtLabel.style.display = 'none';
        menuBtn.style.display = 'flex';
        reportsBtn.style.display = 'none';
        if (usersBtn) usersBtn.style.display = 'none';
        if (orderFilterTabs) orderFilterTabs.style.display = 'none';
        if (orderCashierTab) orderCashierTab.style.display = 'flex';
        if (orderPickerCard) orderPickerCard.style.display = 'none';
        _orderPickerMode = '';
        currentOrderFilter = 'today';
        safeBuildCharts(); // Build charts so Dashboard view works for cashiers too
        showView('pos', document.getElementById('nav-pos-btn') || document.querySelectorAll('.nav-item-btn')[1]);
      } else {
        mgmtLabel.style.display = 'block';
        menuBtn.style.display = 'flex';
        reportsBtn.style.display = 'flex';
        if (usersBtn) usersBtn.style.display = 'flex';
        if (orderFilterTabs) orderFilterTabs.style.display = 'flex';
        if (orderCashierTab) orderCashierTab.style.display = 'none';
        if (orderPickerCard) orderPickerCard.style.display = 'block';
        _orderPickerMode = 'date';
        safeBuildCharts();
        showView('dashboard', document.getElementById('nav-dash-btn') || document.querySelectorAll('.nav-item-btn')[0]);
      }
AAREPLACE;

if (strpos($s, $targetActivate) !== false) {
    $s = str_replace($targetActivate, $replaceActivate, $s);
    echo "Updated activateApp() with UI switching\n";
} else {
    echo "WARNING: targetActivate not found\n";
}

// logout reset
$s = str_replace("      currentUser = null;", "      currentUser = null;\n      _isUserAdmin = false;\n      window.posIsAdmin = false;", $s);

$out = str_replace("\n", "\r\n", $s);
file_put_contents($file, $out);
echo "Updated chipipie-pos.php successfully.\n";

// 2. Update api/orders.php backend enforcement
$ordersFile = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/api/orders.php';
$ordersSrc  = file_get_contents($ordersFile);
$os         = str_replace("\r\n", "\n", $ordersSrc);

$backendTarget = <<<'BEOLD'
function handleFetchOrders() {
    requireLogin();
    $db = getDB();

    $dateFilter = $_GET['date'] ?? 'today';
    $dateMode   = trim($_GET['date_mode'] ?? '');
    $dateVal    = trim($_GET['date_val']  ?? '');
    $where = '';
    $params = [];
BEOLD;

$backendReplace = <<<'BEREPLACE'
function handleFetchOrders() {
    requireLogin();
    $db = getDB();

    $userRole   = $_SESSION['role'] ?? 'cashier';
    $dateFilter = $_GET['date'] ?? 'today';
    $dateMode   = trim($_GET['date_mode'] ?? '');
    $dateVal    = trim($_GET['date_val']  ?? '');
    $where = '';
    $params = [];

    // Cashiers are restricted strictly to today's orders
    if ($userRole === 'cashier') {
        $dateFilter = 'today';
        $dateMode   = '';
        $dateVal    = '';
    }
BEREPLACE;

if (strpos($os, $backendTarget) !== false) {
    $os = str_replace($backendTarget, $backendReplace, $os);
    echo "Added backend enforcement in handleFetchOrders()\n";
} else {
    echo "WARNING: backendTarget not found\n";
}

// Also in handlePrintSummary
$printBackendTarget = <<<'PBOLD'
function handlePrintSummary() {
    requireLogin();
    $db = getDB();
    $dateFilter = $_GET['date'] ?? 'today';
    $dateMode   = trim($_GET['date_mode'] ?? '');
    $dateVal    = trim($_GET['date_val']  ?? '');
PBOLD;

$printBackendReplace = <<<'PBREPLACE'
function handlePrintSummary() {
    requireLogin();
    $db = getDB();
    $userRole   = $_SESSION['role'] ?? 'cashier';
    $dateFilter = $_GET['date'] ?? 'today';
    $dateMode   = trim($_GET['date_mode'] ?? '');
    $dateVal    = trim($_GET['date_val']  ?? '');

    // Cashiers are restricted strictly to today's orders
    if ($userRole === 'cashier') {
        $dateFilter = 'today';
        $dateMode   = '';
        $dateVal    = '';
    }
PBREPLACE;

if (strpos($os, $printBackendTarget) !== false) {
    $os = str_replace($printBackendTarget, $printBackendReplace, $os);
    echo "Added backend enforcement in handlePrintSummary()\n";
} else {
    echo "WARNING: printBackendTarget not found\n";
}

$ordersOut = str_replace("\n", "\r\n", $os);
file_put_contents($ordersFile, $ordersOut);
echo "Updated api/orders.php successfully.\n";
