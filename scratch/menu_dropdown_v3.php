<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);
$s    = str_replace("\r\n", "\n", $src);

// ── 1. Replace entire view-menu HTML ─────────────────────────────────────────
$startMarker = '      <div id="view-menu" class="view">';
$endMarker   = "\n      <!-- REPORTS VIEW -->";
$startPos = strpos($s, $startMarker);
$endPos   = strpos($s, $endMarker);
if ($startPos===false||$endPos===false){echo"ERROR: markers not found\n";exit(1);}

$newHtml = <<<'HTMLBLOCK'
      <div id="view-menu" class="view">
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
          <!-- Two dropdown filter buttons -->
          <div class="menu-filter-row">
            <div class="menu-dd-wrap" id="menu-dd-cafe">
              <button class="menu-dd-btn" id="menu-dd-btn-cafe" onclick="toggleMenuDropdown('cafe')">
                <i class="bi bi-cup-hot-fill me-1"></i>Coffee Side
                <i class="bi bi-chevron-down ms-1 menu-dd-chevron"></i>
              </button>
              <div class="menu-dd-list" id="menu-dd-list-cafe"></div>
            </div>
            <div class="menu-dd-wrap" id="menu-dd-pizza">
              <button class="menu-dd-btn" id="menu-dd-btn-pizza" onclick="toggleMenuDropdown('pizza')">
                <i class="bi bi-circle-fill me-1" style="color:var(--cafe-amber);font-size:11px;"></i>Pizza Side
                <i class="bi bi-chevron-down ms-1 menu-dd-chevron"></i>
              </button>
              <div class="menu-dd-list" id="menu-dd-list-pizza"></div>
            </div>
            <span class="menu-filter-label" id="menu-filter-label"></span>
            <button class="menu-filter-clear" id="menu-filter-clear" onclick="clearMenuFilter()" style="display:none;">
              <i class="bi bi-x-circle me-1"></i>Show All
            </button>
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
      </div>
HTMLBLOCK;

$s = substr($s, 0, $startPos) . $newHtml . substr($s, $endPos);
echo "HTML replaced\n";

// ── 2. Replace accordion CSS with dropdown CSS ────────────────────────────────
$oldCSS = <<<'CSSOLD'
    /* ── Menu branch accordion ── */
    .menu-branch-section { margin-bottom: 10px; }
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
    .menu-branch-body { }
    .menu-branch-body.collapsed { display: none; }
    
CSSOLD;

$newCSS = <<<'CSSNEW'
    /* ── Menu filter dropdowns ── */
    .menu-filter-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding:6px 0 2px 0; }
    .menu-dd-wrap { position:relative; }
    .menu-dd-btn {
      display:inline-flex; align-items:center; gap:2px; padding:7px 14px;
      border-radius:20px; border:1.5px solid var(--cafe-tan);
      background:var(--cafe-cream); color:var(--cafe-brown);
      font-weight:700; font-size:13px; cursor:pointer;
      transition:background 0.15s, border-color 0.15s; white-space:nowrap;
    }
    .menu-dd-btn:hover, .menu-dd-btn.active { background:var(--cafe-tan); border-color:var(--cafe-brown); }
    .menu-dd-chevron { font-size:11px; transition:transform 0.2s; }
    .menu-dd-btn.open .menu-dd-chevron { transform:rotate(-180deg); }
    .menu-dd-list {
      display:none; position:absolute; top:calc(100% + 4px); left:0;
      min-width:190px; background:#fff; border:1.5px solid var(--cafe-tan);
      border-radius:10px; box-shadow:0 4px 18px rgba(0,0,0,0.12);
      z-index:999; padding:4px 0;
    }
    .menu-dd-list.open { display:block; }
    .menu-dd-item {
      padding:9px 16px; cursor:pointer; font-size:13px;
      color:var(--cafe-dark); transition:background 0.12s; white-space:nowrap;
      display:flex; justify-content:space-between; align-items:center;
    }
    .menu-dd-item:hover { background:var(--cafe-cream); }
    .menu-dd-item.selected { font-weight:700; color:var(--cafe-brown); background:var(--cafe-cream); }
    .menu-dd-item.dd-all { border-bottom:1px solid var(--cafe-tan); font-weight:600; }
    .menu-filter-label {
      font-size:12px; color:var(--cafe-brown); font-weight:600;
      background:var(--cafe-cream); border-radius:20px;
      padding:4px 10px; border:1px solid var(--cafe-tan); display:none;
    }
    .menu-filter-label.visible { display:inline-block; }
    .menu-filter-clear {
      font-size:12px; padding:4px 10px; border-radius:20px;
      border:1px solid #fca5a5; background:#fee2e2; color:#b91c1c;
      cursor:pointer; font-weight:600;
    }
    .menu-filter-clear:hover { background:#fecaca; }
    
CSSNEW;

if (strpos($s, $oldCSS) !== false) {
    $s = str_replace($oldCSS, $newCSS, $s);
    echo "CSS replaced (exact match)\n";
} else {
    // Fallback: insert before .menu-table-card
    $insertBefore = "        .menu-table-card {";
    if (strpos($s, $insertBefore) !== false) {
        $s = str_replace($insertBefore, $newCSS . "    .menu-table-card {", $s);
        echo "CSS inserted (fallback)\n";
    } else {
        echo "WARNING: CSS insertion point not found\n";
    }
}

// ── 3. Replace the JS block ───────────────────────────────────────────────────
$jsStart = strpos($s, '    /* ========== MENU TABLE — Branch Accordion ========== */');
$jsEnd   = strpos($s, '    async function toggleAvail(id) {');
if ($jsStart===false){echo"ERROR: JS start marker not found\n";exit(1);}
if ($jsEnd===false){echo"ERROR: JS end marker not found\n";exit(1);}

// Build JS string avoiding PHP interpolation issues by using concatenation
$js  = "    /* ========== MENU TABLE ========== */\n\n";
$js .= "    let currentMenuFilter = null; // null=all, string=category name\n\n";

$js .= "    function buildMenuDropdowns() {\n";
$js .= "      const cafeCats  = posMenuCategories.filter(c => !POS_PIZZA_CAT_IDS.has(c.id));\n";
$js .= "      const pizzaCats = posMenuCategories.filter(c =>  POS_PIZZA_CAT_IDS.has(c.id));\n";
$js .= "      function makeList(elId, cats, side) {\n";
$js .= "        const el = document.getElementById(elId);\n";
$js .= "        if (!el) return;\n";
$js .= "        let h = '<div class=\"menu-dd-item dd-all\" onclick=\"setMenuFilter(null,null)\">All Items</div>';\n";
$js .= "        cats.forEach(c => {\n";
$js .= "          const n = (menuItems || []).filter(m => m.category === c.name).length;\n";
$js .= "          const safe = c.name.replace(/'/g, \"\\\\'\");\n";
$js .= "          h += `<div class=\"menu-dd-item\" onclick=\"setMenuFilter('\${safe}','\${side}')\">\${c.name}<span style=\"opacity:.45;font-size:11px;\">\${n}</span></div>`;\n";
$js .= "        });\n";
$js .= "        el.innerHTML = h;\n";
$js .= "      }\n";
$js .= "      makeList('menu-dd-list-cafe',  cafeCats,  'cafe');\n";
$js .= "      makeList('menu-dd-list-pizza', pizzaCats, 'pizza');\n";
$js .= "    }\n\n";

$js .= "    function toggleMenuDropdown(side) {\n";
$js .= "      const myList    = document.getElementById('menu-dd-list-' + side);\n";
$js .= "      const myBtn     = document.getElementById('menu-dd-btn-' + side);\n";
$js .= "      const otherSide = side === 'cafe' ? 'pizza' : 'cafe';\n";
$js .= "      document.getElementById('menu-dd-list-' + otherSide)?.classList.remove('open');\n";
$js .= "      document.getElementById('menu-dd-btn-'  + otherSide)?.classList.remove('open');\n";
$js .= "      myList?.classList.toggle('open');\n";
$js .= "      myBtn?.classList.toggle('open');\n";
$js .= "    }\n\n";

$js .= "    function setMenuFilter(categoryName, side) {\n";
$js .= "      currentMenuFilter = categoryName;\n";
$js .= "      ['cafe','pizza'].forEach(s => {\n";
$js .= "        document.getElementById('menu-dd-list-' + s)?.classList.remove('open');\n";
$js .= "        document.getElementById('menu-dd-btn-'  + s)?.classList.remove('open');\n";
$js .= "        document.getElementById('menu-dd-btn-'  + s)?.classList.toggle('active', s === side && !!categoryName);\n";
$js .= "      });\n";
$js .= "      document.querySelectorAll('.menu-dd-item').forEach(el => el.classList.remove('selected'));\n";
$js .= "      if (categoryName) {\n";
$js .= "        document.querySelectorAll('.menu-dd-item').forEach(el => {\n";
$js .= "          if (el.textContent.trim().startsWith(categoryName)) el.classList.add('selected');\n";
$js .= "        });\n";
$js .= "      }\n";
$js .= "      const lbl = document.getElementById('menu-filter-label');\n";
$js .= "      const clr = document.getElementById('menu-filter-clear');\n";
$js .= "      if (lbl) { lbl.textContent = categoryName || ''; lbl.classList.toggle('visible', !!categoryName); }\n";
$js .= "      if (clr)  clr.style.display = categoryName ? '' : 'none';\n";
$js .= "      renderMenuTable();\n";
$js .= "    }\n\n";

$js .= "    function clearMenuFilter() { setMenuFilter(null, null); }\n\n";

$js .= "    document.addEventListener('click', function(e) {\n";
$js .= "      if (!e.target.closest('.menu-dd-wrap')) {\n";
$js .= "        document.querySelectorAll('.menu-dd-list').forEach(el => el.classList.remove('open'));\n";
$js .= "        document.querySelectorAll('.menu-dd-btn').forEach(el => el.classList.remove('open'));\n";
$js .= "      }\n";
$js .= "    });\n\n";

$js .= "    function renderMenuTable() {\n";
$js .= "      const q = (document.getElementById('menu-mgmt-search')?.value || '').toLowerCase();\n";
$js .= "      const filtered = (menuItems || []).filter(m => {\n";
$js .= "        const matchQ   = !q || m.name.toLowerCase().includes(q);\n";
$js .= "        const matchCat = !currentMenuFilter || m.category === currentMenuFilter;\n";
$js .= "        return matchQ && matchCat;\n";
$js .= "      });\n";
$js .= "      const tbody = document.getElementById('menu-tbody');\n";
$js .= "      if (!tbody) return;\n";
$js .= "      if (!filtered.length) {\n";
$js .= "        tbody.innerHTML = '<tr><td colspan=\"6\" class=\"text-center text-muted py-4\">No items found</td></tr>';\n";
$js .= "        return;\n";
$js .= "      }\n";
$js .= "      tbody.innerHTML = filtered.map(item => {\n";
$js .= "        const isOut = item.stock <= 0;\n";
$js .= "        const isLow = !isOut && item.stock <= item.minStock;\n";
$js .= "        const sc = isOut ? 'stock-out' : (isLow ? 'stock-low' : 'stock-ok');\n";
$js .= "        let si = '';\n";
$js .= "        if (isOut) si = '<i class=\"bi bi-x-circle-fill me-1\"></i>';\n";
$js .= "        else if (isLow) si = '<i class=\"bi bi-exclamation-triangle-fill me-1\"></i>';\n";
$js .= "        const st = isOut ? 'No Stock' : (isLow ? 'Low Stock (' + item.stock + ')' : item.stock + ' / min ' + item.minStock);\n";
$js .= "        const ico = POS_PIZZA_CAT_IDS.has(Number(item.category_id))\n";
$js .= "          ? '<i class=\"bi bi-circle-fill\" style=\"font-size:12px;color:var(--cafe-amber);\"></i>'\n";
$js .= "          : '<i class=\"bi bi-cup-hot-fill\" style=\"font-size:12px;color:var(--cafe-brown);\"></i>';\n";
$js .= "        const img = item.image\n";
$js .= "          ? `<img src=\"\${item.image}\" style=\"width:24px;height:24px;object-fit:cover;border-radius:50%;margin-right:8px;vertical-align:middle;\">`\n";
$js .= "          : `<span style=\"font-size:14px;margin-right:8px;vertical-align:middle;\">\${ico}</span>`;\n";
$js .= "        const avBtn = `<button class=\"btn-menu-action me-1\" style=\"background:var(--cafe-tan);color:var(--cafe-brown);\" onclick=\"toggleAvail(\${item.id})\" title=\"\${item.available?'Mark Unavailable':'Mark Available'}\">\${item.available?'Mark Unavail.':'Mark Avail.'}</button>`;\n";
$js .= "        const photoBtn = `<button class=\"btn-menu-action me-1\" style=\"background:#dbeafe;color:#1d4ed8;\" onclick=\"posOpenPhotoModal(\${item.id},'\${item.name.replace(/'/g,\\\"\\\\\\\\'\\\")}'  )\" title=\"Photo\"><i class=\"bi bi-camera-fill\"></i>Photo</button>`;\n";
$js .= "        const delBtn = pendingDeleteIds.has(item.id)\n";
$js .= "          ? `<div style=\"display:inline-flex;align-items:center;gap:3px;\"><button class=\"btn-menu-action\" style=\"background:#fef3c7;color:#92400e;border:1px solid #fde68a!important;\" onclick=\"openCancelRemovalModal(\${item.id})\"><i class=\"bi bi-hourglass-split\"></i>Pending</button><button class=\"btn-menu-action\" style=\"background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5!important;\" onclick=\"openCancelRemovalModal(\${item.id})\"><i class=\"bi bi-x-circle\"></i>Cancel</button></div>`\n";
$js .= "          : `<button class=\"btn-menu-action\" style=\"background:#f8d7da;color:#842029;\" onclick=\"deleteMenuItem(\${item.id})\"><i class=\"bi bi-trash-fill\"></i>Delete</button>`;\n";
$js .= "        return `<tr>\n";
$js .= "          <td style=\"white-space:nowrap;\">\${img}<span style=\"font-weight:600;vertical-align:middle;\">\${item.name}</span></td>\n";
$js .= "          <td style=\"white-space:nowrap;\"><span class=\"badge-cat\">\${item.category}</span></td>\n";
$js .= "          <td style=\"font-weight:600;color:var(--cafe-amber);white-space:nowrap;\">₱\${item.price}</td>\n";
$js .= "          <td style=\"white-space:nowrap;\">\n";
$js .= "            <span class=\"stock-badge \${sc}\" style=\"font-size:10.5px;padding:2px 7px;\">\${si}\${st}</span>\n";
$js .= "            <div style=\"margin-top:4px;display:flex;align-items:center;gap:3px;\">\n";
$js .= "              <button class=\"qty-btn\" onclick=\"adjustStock(\${item.id},-1)\">−</button>\n";
$js .= "              <input type=\"number\" min=\"0\" value=\"\${item.stock}\" style=\"width:42px;height:24px;font-size:11.5px;font-weight:700;border:1px solid var(--cafe-tan);border-radius:5px;padding:0 2px;text-align:center;\" onchange=\"setStock(\${item.id},this.value)\">\n";
$js .= "              <button class=\"qty-btn\" onclick=\"adjustStock(\${item.id},1)\">+</button>\n";
$js .= "            </div></td>\n";
$js .= "          <td style=\"white-space:nowrap;\"><span class=\"\${item.available?'badge-success':'badge-pending'}\" style=\"font-size:11px;padding:3px 8px;\">\${item.available?'Available':'Unavailable'}</span></td>\n";
$js .= "          <td style=\"white-space:nowrap;text-align:right;\">\${avBtn}\${photoBtn}\${delBtn}</td>\n";
$js .= "        </tr>`;\n";
$js .= "      }).join('');\n";
$js .= "    }\n\n";

$s = substr($s, 0, $jsStart) . $js . substr($s, $jsEnd);
echo "JS replaced\n";

// ── 4. Hook buildMenuDropdowns into loadMenuFromDB ───────────────────────────
$old_hook = "          pendingDeleteIds = new Set((res.pending_deletions || []).map(Number));";
$new_hook = "          buildMenuDropdowns();\n          pendingDeleteIds = new Set((res.pending_deletions || []).map(Number));";
if (strpos($s, $old_hook) !== false) {
    $s = str_replace($old_hook, $new_hook, $s);
    echo "buildMenuDropdowns hooked\n";
} else {
    echo "WARNING: could not hook buildMenuDropdowns\n";
}

// ── 5. Write file ─────────────────────────────────────────────────────────────
$out = str_replace("\n", "\r\n", $s);
$bytes = file_put_contents($file, $out);
echo "Written: $bytes bytes\nDone.\n";
