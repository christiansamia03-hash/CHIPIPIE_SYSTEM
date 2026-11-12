<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);
$orig = strlen($src);

// ── 1. Replace CSS: old tab-scroll styles → new accordion styles ──────────────
$old_css = '.menu-mgmt-tabs-scroll {
      display: flex;
      gap: 8px;
      overflow-x: auto;
      flex-wrap: nowrap;
      padding: 2px 2px 6px 2px;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: thin;
    }
    .menu-mgmt-tabs-scroll::-webkit-scrollbar {
      height: 4px;
    }
    .menu-mgmt-tabs-scroll::-webkit-scrollbar-thumb {
      background: var(--cafe-tan);
      border-radius: 4px;
    }';

$new_css = '/* ── Menu branch accordion ── */
    .menu-branch-section { margin-bottom: 12px; }
    .menu-branch-header {
      display: flex; align-items: center; gap: 10px; padding: 11px 16px;
      background: var(--cafe-cream); border: 1.5px solid var(--cafe-tan);
      border-radius: 12px; cursor: pointer; font-weight: 700;
      color: var(--cafe-brown); font-size: 14px; user-select: none;
      transition: background 0.15s;
    }
    .menu-branch-header:hover { background: var(--cafe-tan); }
    .menu-branch-chevron { margin-left: auto; transition: transform 0.25s; font-size: 13px; }
    .menu-branch-header.collapsed .menu-branch-chevron { transform: rotate(-90deg); }
    .menu-branch-body { overflow: hidden; }
    .menu-branch-body.collapsed { display: none; }';

// normalize CRLF
$old_css_n = str_replace("\r\n", "\n", $old_css);
$src_n     = str_replace("\r\n", "\n", $src);

if (strpos($src_n, $old_css_n) === false) {
    echo "ERROR: CSS target not found\n";
    exit(1);
}
$src_n = str_replace($old_css_n, $new_css, $src_n);
echo "CSS replaced\n";

// ── 2. Replace view-menu HTML ─────────────────────────────────────────────────
$old_html = '      <div id="view-menu" class="view">
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
          <div class="menu-mgmt-tabs-scroll" id="menu-mgmt-filter-tabs">
            <!-- Dynamically populated in renderMenuTable -->
          </div>
        </div>
        <div class="kpi-card menu-table-card">
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
              <tbody id="menu-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>';

$new_html = '      <div id="view-menu" class="view">
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

        <!-- ☕ ChipiPie Café Section -->
        <div class="menu-branch-section" id="menu-section-cafe">
          <div class="menu-branch-header" onclick="toggleMenuSection(\'cafe\')">
            <i class="bi bi-cup-hot-fill"></i>
            <span>ChipiPie Café Items</span>
            <span id="menu-section-cafe-count" class="badge-cat" style="font-size:11px;">— items</span>
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

        <!-- 🍕 Pizza House Section -->
        <div class="menu-branch-section" id="menu-section-pizza">
          <div class="menu-branch-header" onclick="toggleMenuSection(\'pizza\')">
            <i class="bi bi-circle-fill" style="color:var(--cafe-amber);"></i>
            <span>Pizza House Items</span>
            <span id="menu-section-pizza-count" class="badge-cat" style="font-size:11px;">— items</span>
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

$old_html_n = str_replace("\r\n", "\n", $old_html);
if (strpos($src_n, $old_html_n) === false) {
    echo "ERROR: HTML target not found\n";
    exit(1);
}
$src_n = str_replace($old_html_n, $new_html, $src_n);
echo "HTML replaced\n";

// ── 3. Replace MENU_GROUPS + old filter vars + renderMenuTable JS ─────────────
$old_js = '    // Group definitions matching the POS ordering grid
    const MENU_GROUPS = [
      // ── Coffee Branch ─────────────────────────────────────
      { label: \'All\',         icon: \'bi-grid\',              cats: null },   // null = no filter
      { label: \'Coffee\',      icon: \'bi-cup-hot-fill\',      cats: [\'Hot Coffee\', \'Iced Coffee\'] },
      { label: \'Non-Coffee\',  icon: \'bi-cup-straw\',         cats: [\'Classic Milktea\', \'Virgin Mojito\', \'Citrus Popping Boba\', \'Soda Float\', \'Matcha Series\', \'Frappé\'] },
      { label: \'Snack\',       icon: \'bi-egg-fried\',         cats: [\'Fork and Fun\'] },
      // ── Pizza Branch ──────────────────────────────────────
      { label: \'Pizza\',       icon: \'bi-circle\',            cats: [\'Pizza\'] },
      { label: \'Quesadilla\',  icon: \'bi-grid-3x3-gap\',      cats: [\'Quesadilla\'] },
      { label: \'Patatas\',     icon: \'bi-potato\',         cats: [\'Patatas\'] },
      { label: \'Pasta\',       icon: \'bi-noodles\',             cats: [\'Pasta\'] },
      { label: \'Chickenpops\', icon: \'bi-drumstick\',         cats: [\'Chickenpops\'] },
      { label: \'Combo\',       icon: \'bi-collection\',        cats: [\'Combo\', \'Gipitmeal\'] },
    ];

    let currentMenuMgmtGroup = MENU_GROUPS[0]; // default = All

    function setMenuMgmtFilter(label, btn) {
      document.querySelectorAll(\'#menu-mgmt-filter-tabs .cat-pill\').forEach(b => b.classList.remove(\'active\'));
      btn.classList.add(\'active\');
      currentMenuMgmtGroup = MENU_GROUPS.find(g => g.label === label) || MENU_GROUPS[0];
      renderMenuTable();
    }

    /* ========== MENU TABLE ========== */
    let menuMgmtTabsBuilt = false;

    function renderMenuTable() {
      // Build tabs once
      if (!menuMgmtTabsBuilt) {
        const tabsEl = document.getElementById(\'menu-mgmt-filter-tabs\');
        if (tabsEl) {
          tabsEl.innerHTML = MENU_GROUPS
            .filter(g => g.label === \'All\' || (menuItems || []).some(m => g.cats && g.cats.includes(m.category)))
            .map((g, i) => {
              const sep = (g.label === \'Pizza\') ? \'<span style="width:1px;background:var(--cafe-tan);height:22px;display:inline-block;margin:0 4px;vertical-align:middle;"></span>\' : \'\';
              return `${sep}<button class="cat-pill${i === 0 ? \' active\' : \'\'}" onclick="setMenuMgmtFilter(\'${g.label}\', this)"><i class="bi ${g.icon} me-1"></i>${g.label}</button>`;
            })
            .join(\'\');
          menuMgmtTabsBuilt = true;
        }
      }

      const q = (document.getElementById(\'menu-mgmt-search\')?.value || \'\').toLowerCase();
      const filtered = menuItems.filter(m => {
        const matchQ = !q || m.name.toLowerCase().includes(q);
        const matchCat = !currentMenuMgmtGroup.cats || currentMenuMgmtGroup.cats.includes(m.category);
        return matchQ && matchCat;
      });

      document.getElementById(\'menu-tbody\').innerHTML = filtered.map(item => {
        const isOut = item.stock <= 0;
        const isLow = !isOut && item.stock <= item.minStock;
        const stockBadgeClass = isOut ? \'stock-out\' : (isLow ? \'stock-low\' : \'stock-ok\');
        
        let stockIcon = \'\';
        if (isOut) stockIcon = \'<i class="bi bi-x-circle-fill me-1"></i>\';
        else if (isLow) stockIcon = \'<i class="bi bi-exclamation-triangle-fill me-1"></i>\';

        const stockText = isOut ? \'No Stock\' : (isLow ? \'Low Stock (\' + item.stock + \')\' : item.stock + \' / min \' + item.minStock);
        return `
    <tr>
      <td style="white-space:nowrap;">
        ${item.image ? `<img src="${item.image}" style="width:24px; height:24px; object-fit:cover; border-radius:50%; margin-right:8px; vertical-align:middle;">` : `<span style="font-size:16px; margin-right:8px; vertical-align:middle; color:var(--cafe-brown);"><i class="bi bi-cup-hot-fill"></i></span>`}
        <span style="font-weight:600; vertical-align:middle;">${item.name}</span>
      </td>
      <td style="white-space:nowrap;"><span class="badge-cat">${item.category}</span></td>
      <td style="font-weight:600; color:var(--cafe-amber); white-space:nowrap;">₱${item.price}</td>
      <td style="white-space:nowrap;">
        <span class="stock-badge ${stockBadgeClass}" style="font-size:10.5px; padding:2px 7px;">${stockIcon}${stockText}</span>
        <div style="margin-top:4px; display:flex; align-items:center; gap:3px;">
          <button class="qty-btn" onclick="adjustStock(${item.id}, -1)" title="Decrease stock">−</button>
          <input type="number" min="0" value="${item.stock}" style="width:42px; height:24px; font-size:11.5px; font-weight:700; border:1px solid var(--cafe-tan); border-radius:5px; padding:0 2px; text-align:center;" onchange="setStock(${item.id}, this.value)" title="Direct stock edit">
          <button class="qty-btn" onclick="adjustStock(${item.id}, 1)" title="Increase stock">+</button>
        </div>
      </td>
      <td style="white-space:nowrap;">
        <span class="${item.available ? \'badge-success\' : \'badge-pending\'}" style="font-size:11px; padding:3px 8px;">${item.available ? \'Available\' : \'Unavailable\'}</span>
      </td>
      <td style="white-space:nowrap; text-align:right;">
        <button class="btn-menu-action me-1" style="background:var(--cafe-tan); color:var(--cafe-brown);" onclick="toggleAvail(${item.id})" title="${item.available ? \'Mark Unavailable\' : \'Mark Available\'}">
          ${item.available ? \'Mark Unavail.\' : \'Mark Avail.\'}
        </button>
        <button class="btn-menu-action me-1" style="background:#dbeafe; color:#1d4ed8;" onclick="posOpenPhotoModal(${item.id}, \'${item.name.replace(/\'/g, "\\\\\'")}\')" title="Upload / change photo">
          <i class="bi bi-camera-fill"></i>Photo
        </button>
        ${pendingDeleteIds.has(item.id)
          ? `<div style="display:inline-flex;align-items:center;gap:3px;">
               <button class="btn-menu-action" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a !important;" onclick="openCancelRemovalModal(${item.id})" title="Click to view or cancel pending removal">
                 <i class="bi bi-hourglass-split"></i>Pending
               </button>
               <button class="btn-menu-action" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5 !important;" onclick="openCancelRemovalModal(${item.id})" title="Cancel this pending removal request">
                 <i class="bi bi-x-circle"></i>Cancel
               </button>
             </div>`
          : `<button class="btn-menu-action" style="background:#f8d7da; color:#842029;" onclick="deleteMenuItem(${item.id})" title="Delete item">
                <i class="bi bi-trash-fill"></i>Delete
              </button>`
        }
      </td>
    </tr>`;
      }).join(\'\');
    }';

$new_js = '    /* ========== MENU TABLE — Branch Accordion ========== */

    function toggleMenuSection(branch) {
      const body = document.getElementById(\'menu-section-\' + branch + \'-body\');
      const hdr  = document.querySelector(\'#menu-section-\' + branch + \' .menu-branch-header\');
      if (!body) return;
      const collapsed = body.classList.toggle(\'collapsed\');
      if (hdr) hdr.classList.toggle(\'collapsed\', collapsed);
    }

    function renderMenuTable() {
      const q = (document.getElementById(\'menu-mgmt-search\')?.value || \'\').toLowerCase();

      const cafeItems  = (menuItems || []).filter(m => !POS_PIZZA_CAT_IDS.has(Number(m.category_id)) && (!q || m.name.toLowerCase().includes(q)));
      const pizzaItems = (menuItems || []).filter(m =>  POS_PIZZA_CAT_IDS.has(Number(m.category_id)) && (!q || m.name.toLowerCase().includes(q)));

      function buildRow(item) {
        const isOut = item.stock <= 0;
        const isLow = !isOut && item.stock <= item.minStock;
        const stockBadgeClass = isOut ? \'stock-out\' : (isLow ? \'stock-low\' : \'stock-ok\');
        let stockIcon = \'\';
        if (isOut) stockIcon = \'<i class="bi bi-x-circle-fill me-1"></i>\';
        else if (isLow) stockIcon = \'<i class="bi bi-exclamation-triangle-fill me-1"></i>\';
        const stockText = isOut ? \'No Stock\' : (isLow ? \'Low Stock (\' + item.stock + \')\' : item.stock + \' / min \' + item.minStock);
        const icon = POS_PIZZA_CAT_IDS.has(Number(item.category_id))
          ? \'<i class="bi bi-circle-fill" style="font-size:14px;color:var(--cafe-amber);"></i>\'
          : \'<i class="bi bi-cup-hot-fill" style="font-size:14px;color:var(--cafe-brown);"></i>\';
        return `
    <tr>
      <td style="white-space:nowrap;">
        ${item.image ? `<img src="${item.image}" style="width:24px; height:24px; object-fit:cover; border-radius:50%; margin-right:8px; vertical-align:middle;">` : `<span style="font-size:16px; margin-right:8px; vertical-align:middle;">${icon}</span>`}
        <span style="font-weight:600; vertical-align:middle;">${item.name}</span>
      </td>
      <td style="white-space:nowrap;"><span class="badge-cat">${item.category}</span></td>
      <td style="font-weight:600; color:var(--cafe-amber); white-space:nowrap;">₱${item.price}</td>
      <td style="white-space:nowrap;">
        <span class="stock-badge ${stockBadgeClass}" style="font-size:10.5px; padding:2px 7px;">${stockIcon}${stockText}</span>
        <div style="margin-top:4px; display:flex; align-items:center; gap:3px;">
          <button class="qty-btn" onclick="adjustStock(${item.id}, -1)" title="Decrease stock">−</button>
          <input type="number" min="0" value="${item.stock}" style="width:42px; height:24px; font-size:11.5px; font-weight:700; border:1px solid var(--cafe-tan); border-radius:5px; padding:0 2px; text-align:center;" onchange="setStock(${item.id}, this.value)" title="Direct stock edit">
          <button class="qty-btn" onclick="adjustStock(${item.id}, 1)" title="Increase stock">+</button>
        </div>
      </td>
      <td style="white-space:nowrap;">
        <span class="${item.available ? \'badge-success\' : \'badge-pending\'}" style="font-size:11px; padding:3px 8px;">${item.available ? \'Available\' : \'Unavailable\'}</span>
      </td>
      <td style="white-space:nowrap; text-align:right;">
        <button class="btn-menu-action me-1" style="background:var(--cafe-tan); color:var(--cafe-brown);" onclick="toggleAvail(${item.id})" title="${item.available ? \'Mark Unavailable\' : \'Mark Available\'}">
          ${item.available ? \'Mark Unavail.\' : \'Mark Avail.\'}
        </button>
        <button class="btn-menu-action me-1" style="background:#dbeafe; color:#1d4ed8;" onclick="posOpenPhotoModal(${item.id}, \'${item.name.replace(/\'/g, "\\\\\'")}\')" title="Upload / change photo">
          <i class="bi bi-camera-fill"></i>Photo
        </button>
        ${pendingDeleteIds.has(item.id)
          ? `<div style="display:inline-flex;align-items:center;gap:3px;">
               <button class="btn-menu-action" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a !important;" onclick="openCancelRemovalModal(${item.id})" title="Click to view or cancel pending removal">
                 <i class="bi bi-hourglass-split"></i>Pending
               </button>
               <button class="btn-menu-action" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5 !important;" onclick="openCancelRemovalModal(${item.id})" title="Cancel this pending removal request">
                 <i class="bi bi-x-circle"></i>Cancel
               </button>
             </div>`
          : `<button class="btn-menu-action" style="background:#f8d7da; color:#842029;" onclick="deleteMenuItem(${item.id})" title="Delete item">
                <i class="bi bi-trash-fill"></i>Delete
              </button>`
        }
      </td>
    </tr>`;
      }

      const noRowCafe  = \'<tr><td colspan="6" class="text-center text-muted py-3">No café items found</td></tr>\';
      const noRowPizza = \'<tr><td colspan="6" class="text-center text-muted py-3">No pizza items found</td></tr>\';

      const cafeTbody  = document.getElementById(\'menu-tbody-cafe\');
      const pizzaTbody = document.getElementById(\'menu-tbody-pizza\');
      if (cafeTbody)  cafeTbody.innerHTML  = cafeItems.length  ? cafeItems.map(buildRow).join(\'\')  : noRowCafe;
      if (pizzaTbody) pizzaTbody.innerHTML = pizzaItems.length ? pizzaItems.map(buildRow).join(\'\') : noRowPizza;

      const cafeCount  = document.getElementById(\'menu-section-cafe-count\');
      const pizzaCount = document.getElementById(\'menu-section-pizza-count\');
      if (cafeCount)  cafeCount.textContent  = cafeItems.length  + \' items\';
      if (pizzaCount) pizzaCount.textContent = pizzaItems.length + \' items\';
    }';

$old_js_n = str_replace("\r\n", "\n", $old_js);
if (strpos($src_n, $old_js_n) === false) {
    echo "ERROR: JS target not found\n";
    // Try to find partial match
    $lines = explode("\n", $old_js_n);
    foreach ($lines as $i => $line) {
        if (strpos($src_n, trim($line)) === false && trim($line) !== '') {
            echo "  Missing line $i: " . substr($line, 0, 80) . "\n";
        }
    }
    exit(1);
}
$src_n = str_replace($old_js_n, $new_js, $src_n);
echo "JS replaced\n";

// ── 4. Remove the menuMgmtTabsBuilt = false reset (line ~9735) ───────────────
// Just replace it with a comment so nothing breaks
$src_n = str_replace(
    "          menuMgmtTabsBuilt = false;",
    "          // menuMgmtTabsBuilt removed (accordion layout no longer uses it)",
    $src_n
);
echo "menuMgmtTabsBuilt reset line removed\n";

// ── 5. Write back ─────────────────────────────────────────────────────────────
// Restore CRLF for Windows
$out = str_replace("\n", "\r\n", $src_n);
$bytes = file_put_contents($file, $out);
echo "Written: $bytes bytes (was $orig)\n";
echo "Done.\n";
