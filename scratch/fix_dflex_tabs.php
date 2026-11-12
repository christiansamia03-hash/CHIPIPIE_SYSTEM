<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);
$s    = str_replace("\r\n", "\n", $src);

$old = <<<'HTMLOLD'
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
HTMLOLD;

$new = <<<'HTMLNEW'
          <div id="order-filter-tabs" style="display:flex;gap:8px;flex-wrap:wrap;">
            <button id="ofilter-pick-date"  class="cat-pill active" onclick="setOrderPickerMode('date')"><i class="bi bi-calendar-event"></i> Date</button>
            <button id="ofilter-pick-week"  class="cat-pill" onclick="setOrderPickerMode('week')"><i class="bi bi-calendar-range"></i> Week</button>
            <button id="ofilter-pick-month" class="cat-pill" onclick="setOrderPickerMode('month')"><i class="bi bi-calendar-month"></i> Month</button>
            <button id="ofilter-pick-year"  class="cat-pill" onclick="setOrderPickerMode('year')"><i class="bi bi-calendar"></i> Year</button>
          </div>
          <!-- Cashier Single Tab (Today only) -->
          <div id="order-cashier-tab" style="display:none;gap:8px;flex-wrap:wrap;">
            <button class="cat-pill active" style="cursor:default;"><i class="bi bi-clock-history me-1"></i>Today</button>
          </div>
HTMLNEW;

if (strpos($s, $old) !== false) {
    $s = str_replace($old, $new, $s);
    echo "Fixed order tabs without d-flex !important conflict\n";
} else {
    echo "ERROR: old tabs not found\n";
    exit(1);
}

$out = str_replace("\n", "\r\n", $s);
file_put_contents($file, $out);
echo "Done.\n";
