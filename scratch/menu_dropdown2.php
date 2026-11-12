<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);

// Normalize to LF for matching
$s = str_replace("\r\n", "\n", $src);

// ── Find exact line ranges using line anchors ─────────────────────────────────
// We'll replace from the MENU_GROUPS comment down through end of renderMenuTable function (line 8595 ~ closing brace)
// Strategy: find start marker and end marker in normalized content

$startMarker = '    // Group definitions matching the POS ordering grid';
$endMarker   = "    async function toggleAvail(id) {";

$startPos = strpos($s, $startMarker);
$endPos   = strpos($s, $endMarker);

if ($startPos === false) { echo "ERROR: start marker not found\n"; exit(1); }
if ($endPos === false)   { echo "ERROR: end marker not found\n"; exit(1); }

echo "Start pos: $startPos\n";
echo "End pos:   $endPos\n";

$oldBlock = substr($s, $startPos, $endPos - $startPos);
echo "Block length: " . strlen($oldBlock) . "\n";
echo "Block first 100: " . substr($oldBlock, 0, 100) . "\n";

$newBlock = '    /* ========== MENU TABLE — Branch Accordion ========== */

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
      <td style="font-weight:600; color:var(--cafe-amber); white-space:nowrap;">&#x20B1;${item.price}</td>
      <td style="white-space:nowrap;">
        <span class="stock-badge ${stockBadgeClass}" style="font-size:10.5px; padding:2px 7px;">${stockIcon}${stockText}</span>
        <div style="margin-top:4px; display:flex; align-items:center; gap:3px;">
          <button class="qty-btn" onclick="adjustStock(${item.id}, -1)" title="Decrease stock">&minus;</button>
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
        <button class="btn-menu-action me-1" style="background:#dbeafe; color:#1d4ed8;" onclick="posOpenPhotoModal(${item.id}, \'${item.name.replace(/\'/g, "\\\\\'")}\' )" title="Upload / change photo">
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

      const noRowCafe  = \'<tr><td colspan="6" class="text-center text-muted py-3">No cafe items found</td></tr>\';
      const noRowPizza = \'<tr><td colspan="6" class="text-center text-muted py-3">No pizza items found</td></tr>\';

      const cafeTbody  = document.getElementById(\'menu-tbody-cafe\');
      const pizzaTbody = document.getElementById(\'menu-tbody-pizza\');
      if (cafeTbody)  cafeTbody.innerHTML  = cafeItems.length  ? cafeItems.map(buildRow).join(\'\')  : noRowCafe;
      if (pizzaTbody) pizzaTbody.innerHTML = pizzaItems.length ? pizzaItems.map(buildRow).join(\'\') : noRowPizza;

      const cafeCount  = document.getElementById(\'menu-section-cafe-count\');
      const pizzaCount = document.getElementById(\'menu-section-pizza-count\');
      if (cafeCount)  cafeCount.textContent  = cafeItems.length  + \' items\';
      if (pizzaCount) pizzaCount.textContent = pizzaItems.length + \' items\';
    }

';

$s = substr($s, 0, $startPos) . $newBlock . substr($s, $endPos);

// Also neutralize the menuMgmtTabsBuilt = false reset
$s = str_replace(
    "          menuMgmtTabsBuilt = false;",
    "          // menuMgmtTabsBuilt removed (accordion layout no longer uses it)",
    $s
);

// Restore CRLF
$out = str_replace("\n", "\r\n", $s);
$bytes = file_put_contents($file, $out);
echo "Written: $bytes bytes\nDone.\n";
