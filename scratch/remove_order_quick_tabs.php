<?php
// 1. Update chipipie-pos.php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);
$s    = str_replace("\r\n", "\n", $src);

// Replace the order-filter-tabs HTML
$oldTabs = <<<'HTMLOLD'
          <div class="d-flex gap-2 flex-wrap" id="order-filter-tabs">
            <button id="ofilter-today" class="cat-pill active" onclick="setOrderFilter('today', this)">Today</button>
            <button id="ofilter-week" class="cat-pill" onclick="setOrderFilter('week',  this)">This Week</button>
            <button id="ofilter-month" class="cat-pill" onclick="setOrderFilter('month', this)">This Month</button>
            <button id="ofilter-year" class="cat-pill" onclick="setOrderFilter('year',  this)">This Year</button>
            <!-- Separator -->
            <span style="width:1px;background:#ddd;margin:0 4px;align-self:stretch;"></span>
            <button id="ofilter-pick-date"  class="cat-pill" onclick="setOrderPickerMode('date')"><i class="bi bi-calendar-event"></i> Date</button>
            <button id="ofilter-pick-week"  class="cat-pill" onclick="setOrderPickerMode('week')"><i class="bi bi-calendar-range"></i> Week</button>
            <button id="ofilter-pick-month" class="cat-pill" onclick="setOrderPickerMode('month')"><i class="bi bi-calendar-month"></i> Month</button>
            <button id="ofilter-pick-year"  class="cat-pill" onclick="setOrderPickerMode('year')"><i class="bi bi-calendar"></i> Year</button>
          </div>
HTMLOLD;

$newTabs = <<<'HTMLNEW'
          <div class="d-flex gap-2 flex-wrap" id="order-filter-tabs">
            <button id="ofilter-pick-date"  class="cat-pill active" onclick="setOrderPickerMode('date')"><i class="bi bi-calendar-event"></i> Date</button>
            <button id="ofilter-pick-week"  class="cat-pill" onclick="setOrderPickerMode('week')"><i class="bi bi-calendar-range"></i> Week</button>
            <button id="ofilter-pick-month" class="cat-pill" onclick="setOrderPickerMode('month')"><i class="bi bi-calendar-month"></i> Month</button>
            <button id="ofilter-pick-year"  class="cat-pill" onclick="setOrderPickerMode('year')"><i class="bi bi-calendar"></i> Year</button>
          </div>
HTMLNEW;

if (strpos($s, $oldTabs) !== false) {
    $s = str_replace($oldTabs, $newTabs, $s);
    echo "Replaced order-filter-tabs HTML\n";
} else {
    echo "ERROR: oldTabs not found\n";
    exit(1);
}

// Make order-picker-card default visible with date section visible
$oldCard = <<<'CARDOLD'
        <!-- Orders Date Picker Card -->
        <div id="order-picker-card" class="kpi-card mb-3" style="display:none;padding:12px 16px;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <i class="bi bi-calendar3" style="color:var(--cafe-brown);font-size:15px;"></i>
            <span style="font-size:13px;font-weight:700;color:var(--cafe-brown);">Custom Date Filter</span>
            <span id="order-picker-summary" style="font-size:12px;color:#888;margin-left:6px;"></span>
          </div>
          <!-- DATE -->
          <div id="order-picker-date" class="order-picker-section" style="display:none;">
CARDOLD;

$newCard = <<<'CARDNEW'
        <!-- Orders Date Picker Card -->
        <div id="order-picker-card" class="kpi-card mb-3" style="display:block;padding:12px 16px;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <i class="bi bi-calendar3" style="color:var(--cafe-brown);font-size:15px;"></i>
            <span style="font-size:13px;font-weight:700;color:var(--cafe-brown);">Custom Date Filter</span>
            <span id="order-picker-summary" style="font-size:12px;color:#888;margin-left:6px;"></span>
          </div>
          <!-- DATE -->
          <div id="order-picker-date" class="order-picker-section" style="display:block;">
CARDNEW;

if (strpos($s, $oldCard) !== false) {
    $s = str_replace($oldCard, $newCard, $s);
    echo "Made order-picker-card default visible\n";
} else {
    echo "WARNING: oldCard not found\n";
}

// Update JS default state: _orderPickerMode = 'date'
$oldJsVar = "    let currentOrderFilter = 'today';\n    let _orderPickerMode   = '';  // '' | 'date' | 'week' | 'month' | 'year'";
$newJsVar = "    let currentOrderFilter = 'today';\n    let _orderPickerMode   = 'date';  // default to Date picker mode";
if (strpos($s, $oldJsVar) !== false) {
    $s = str_replace($oldJsVar, $newJsVar, $s);
    echo "Set _orderPickerMode default to 'date'\n";
}

// Update showView('orders') to initialize picker mode if needed
$oldNav = "      if (name === 'orders') renderOrders();";
$newNav = "      if (name === 'orders') { if (!_orderPickerMode) { setOrderPickerMode('date'); } else { applyOrderPickerFilter(); } }";
if (strpos($s, $oldNav) !== false) {
    $s = str_replace($oldNav, $newNav, $s);
    echo "Updated showView('orders') navigation\n";
}

// Update printOrders to support picker parameters
$oldPrint = <<<'PRINTOLD'
    /* ========== PRINT ORDERS REPORT ========== */
    async function printOrders() {
      const filterLabels = { today: 'Today', week: 'This Week', month: 'This Month', year: 'This Year' };
      const label = filterLabels[currentOrderFilter] || 'Today';

      try {
        const res = await api(`orders.php?action=print_summary&date=${currentOrderFilter}`);
PRINTOLD;

$newPrint = <<<'PRINTNEW'
    /* ========== PRINT ORDERS REPORT ========== */
    async function printOrders() {
      let printUrl = `orders.php?action=print_summary&date=${currentOrderFilter}`;
      let label = 'Today';
      if (_orderPickerMode && _orderPickerVal) {
        printUrl = `orders.php?action=print_summary&date_mode=${_orderPickerMode}&date_val=${encodeURIComponent(_orderPickerVal)}`;
        label = document.getElementById('order-picker-summary')?.textContent?.replace('Showing: ','') || _orderPickerVal;
      }

      try {
        const res = await api(printUrl);
PRINTNEW;

if (strpos($s, $oldPrint) !== false) {
    $s = str_replace($oldPrint, $newPrint, $s);
    echo "Updated printOrders to support date_mode & date_val\n";
}

$out = str_replace("\n", "\r\n", $s);
file_put_contents($file, $out);
echo "Updated chipipie-pos.php successfully.\n";
