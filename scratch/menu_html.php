<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);
$s    = str_replace("\r\n", "\n", $src);

// Find the view-menu div and its closing tag
$startMarker = '      <div id="view-menu" class="view">';
$endMarker   = "\n      <!-- REPORTS VIEW -->";

$startPos = strpos($s, $startMarker);
$endPos   = strpos($s, $endMarker);

if ($startPos === false) { echo "ERROR: start not found\n"; exit(1); }
if ($endPos   === false) { echo "ERROR: end not found\n"; exit(1); }

echo "Found view-menu: $startPos → $endPos\n";
$old = substr($s, $startPos, $endPos - $startPos);
echo "Old block:\n---\n" . substr($old, 0, 200) . "\n...\n";

$newHtml = '      <div id="view-menu" class="view">
        <div class="menu-mgmt-toolbar">
          <div class="menu-mgmt-top-row">
            <div class="search-wrap menu-mgmt-search-wrap">
              <i class="bi bi-search"></i>
              <input type="text" class="search-input" id="menu-mgmt-search" placeholder="Search menu..." oninput="renderMenuTable()">
            </div>
            <button class="btn-cafe-primary menu-mgmt-add-btn" onclick="populateAddItemCategorySelect()" data-bs-toggle="modal"
              data-bs-target="#addItemModal">
              <i class="bi bi-plus-circle me-1"></i><span>Add Item</span>
            </button>
          </div>
        </div>

        <!-- ChipiPie Cafe Section -->
        <div class="menu-branch-section" id="menu-section-cafe">
          <div class="menu-branch-header" onclick="toggleMenuSection(\'cafe\')">
            <i class="bi bi-cup-hot-fill"></i>
            <span>ChipiPie Cafe Items</span>
            <span id="menu-section-cafe-count" class="badge-cat" style="font-size:11px;">-- items</span>
            <i class="bi bi-chevron-down menu-branch-chevron" id="menu-section-cafe-chevron"></i>
          </div>
          <div class="menu-branch-body" id="menu-section-cafe-body">
            <div class="kpi-card menu-table-card" style="margin-top:0;border-radius:0 0 12px 12px;">
              <div class="table-responsive-wrapper">
                <table class="data-table menu-mgmt-table">
                  <thead>
                    <tr>
                      <th style="min-width:130px;">Item</th>
                      <th style="min-width:85px;">Category</th>
                      <th style="min-width:65px;">Price</th>
                      <th style="min-width:115px;">Stock</th>
                      <th style="min-width:90px;">Availability</th>
                      <th style="min-width:170px; text-align:right;">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="menu-tbody-cafe"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Pizza House Section -->
        <div class="menu-branch-section" id="menu-section-pizza">
          <div class="menu-branch-header" onclick="toggleMenuSection(\'pizza\')">
            <i class="bi bi-circle-fill" style="color:var(--cafe-amber);"></i>
            <span>Pizza House Items</span>
            <span id="menu-section-pizza-count" class="badge-cat" style="font-size:11px;">-- items</span>
            <i class="bi bi-chevron-down menu-branch-chevron" id="menu-section-pizza-chevron"></i>
          </div>
          <div class="menu-branch-body" id="menu-section-pizza-body">
            <div class="kpi-card menu-table-card" style="margin-top:0;border-radius:0 0 12px 12px;">
              <div class="table-responsive-wrapper">
                <table class="data-table menu-mgmt-table">
                  <thead>
                    <tr>
                      <th style="min-width:130px;">Item</th>
                      <th style="min-width:85px;">Category</th>
                      <th style="min-width:65px;">Price</th>
                      <th style="min-width:115px;">Stock</th>
                      <th style="min-width:90px;">Availability</th>
                      <th style="min-width:170px; text-align:right;">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="menu-tbody-pizza"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>';

$s = substr($s, 0, $startPos) . $newHtml . substr($s, $endPos);

$out = str_replace("\n", "\r\n", $s);
$bytes = file_put_contents($file, $out);
echo "Written: $bytes bytes. Done.\n";
