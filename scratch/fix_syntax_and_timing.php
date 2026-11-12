<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);
$s    = str_replace("\r\n", "\n", $src);

// ── 1. Fix line 8644 syntax error and clean up renderMenuTable / buildMenuDropdowns ──
$jsStartMarker = '    /* ========== MENU TABLE ========== */';
$jsEndMarker   = '    async function toggleAvail(id) {';

$p1 = strpos($s, $jsStartMarker);
$p2 = strpos($s, $jsEndMarker);

if ($p1 === false || $p2 === false) {
    echo "ERROR: JS markers not found\n";
    exit(1);
}

$newJS = <<<'JSEOF'
    /* ========== MENU TABLE ========== */

    let currentMenuFilter = null; // null=all, string=category name

    function buildMenuDropdowns() {
      if (!window.posMenuCategories || !posMenuCategories.length) return;
      const cafeCats  = posMenuCategories.filter(c => !POS_PIZZA_CAT_IDS.has(Number(c.id)));
      const pizzaCats = posMenuCategories.filter(c =>  POS_PIZZA_CAT_IDS.has(Number(c.id)));

      function makeList(elId, cats, side) {
        const el = document.getElementById(elId);
        if (!el) return;
        let h = '<div class="menu-dd-item dd-all" onclick="setMenuFilter(null,null)"><span>All Items</span></div>';
        cats.forEach(c => {
          const n = (menuItems || []).filter(m => m.category === c.name).length;
          const safe = (c.name || '').replace(/'/g, "\\'");
          h += `<div class="menu-dd-item" onclick="setMenuFilter('${safe}','${side}')"><span>${c.name}</span><span style="opacity:.5;font-size:11px;margin-left:8px;">${n}</span></div>`;
        });
        el.innerHTML = h;
      }
      makeList('menu-dd-list-cafe',  cafeCats,  'cafe');
      makeList('menu-dd-list-pizza', pizzaCats, 'pizza');
    }

    function toggleMenuDropdown(side) {
      buildMenuDropdowns();
      const myList    = document.getElementById('menu-dd-list-' + side);
      const myBtn     = document.getElementById('menu-dd-btn-' + side);
      const otherSide = side === 'cafe' ? 'pizza' : 'cafe';
      document.getElementById('menu-dd-list-' + otherSide)?.classList.remove('open');
      document.getElementById('menu-dd-btn-'  + otherSide)?.classList.remove('open');
      myList?.classList.toggle('open');
      myBtn?.classList.toggle('open');
    }

    function setMenuFilter(categoryName, side) {
      currentMenuFilter = categoryName;
      ['cafe','pizza'].forEach(s => {
        document.getElementById('menu-dd-list-' + s)?.classList.remove('open');
        document.getElementById('menu-dd-btn-'  + s)?.classList.remove('open');
        document.getElementById('menu-dd-btn-'  + s)?.classList.toggle('active', s === side && !!categoryName);
      });
      document.querySelectorAll('.menu-dd-item').forEach(el => el.classList.remove('selected'));
      if (categoryName) {
        document.querySelectorAll('.menu-dd-item').forEach(el => {
          if (el.textContent.trim().startsWith(categoryName)) el.classList.add('selected');
        });
      }
      const lbl = document.getElementById('menu-filter-label');
      const clr = document.getElementById('menu-filter-clear');
      if (lbl) { lbl.textContent = categoryName || ''; lbl.classList.toggle('visible', !!categoryName); }
      if (clr)  clr.style.display = categoryName ? '' : 'none';
      renderMenuTable();
    }

    function clearMenuFilter() { setMenuFilter(null, null); }

    document.addEventListener('click', function(e) {
      if (!e.target.closest('.menu-dd-wrap')) {
        document.querySelectorAll('.menu-dd-list').forEach(el => el.classList.remove('open'));
        document.querySelectorAll('.menu-dd-btn').forEach(el => el.classList.remove('open'));
      }
    });

    function renderMenuTable() {
      buildMenuDropdowns();
      const q = (document.getElementById('menu-mgmt-search')?.value || '').toLowerCase();
      const filtered = (menuItems || []).filter(m => {
        const matchQ   = !q || m.name.toLowerCase().includes(q);
        const matchCat = !currentMenuFilter || m.category === currentMenuFilter;
        return matchQ && matchCat;
      });
      const tbody = document.getElementById('menu-tbody');
      if (!tbody) return;
      if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No items found</td></tr>';
        return;
      }
      tbody.innerHTML = filtered.map(item => {
        const isOut = item.stock <= 0;
        const isLow = !isOut && item.stock <= item.minStock;
        const sc = isOut ? 'stock-out' : (isLow ? 'stock-low' : 'stock-ok');
        let si = '';
        if (isOut) si = '<i class="bi bi-x-circle-fill me-1"></i>';
        else if (isLow) si = '<i class="bi bi-exclamation-triangle-fill me-1"></i>';
        const st = isOut ? 'No Stock' : (isLow ? 'Low Stock (' + item.stock + ')' : item.stock + ' / min ' + item.minStock);
        const ico = POS_PIZZA_CAT_IDS.has(Number(item.category_id))
          ? '<i class="bi bi-circle-fill" style="font-size:12px;color:var(--cafe-amber);"></i>'
          : '<i class="bi bi-cup-hot-fill" style="font-size:12px;color:var(--cafe-brown);"></i>';
        const img = item.image
          ? `<img src="${item.image}" style="width:24px;height:24px;object-fit:cover;border-radius:50%;margin-right:8px;vertical-align:middle;">`
          : `<span style="font-size:14px;margin-right:8px;vertical-align:middle;">${ico}</span>`;
        const avBtn = `<button class="btn-menu-action me-1" style="background:var(--cafe-tan);color:var(--cafe-brown);" onclick="toggleAvail(${item.id})" title="${item.available?'Mark Unavailable':'Mark Available'}">${item.available?'Mark Unavail.':'Mark Avail.'}</button>`;
        const safeName = (item.name || '').replace(/'/g, "\\'");
        const photoBtn = `<button class="btn-menu-action me-1" style="background:#dbeafe;color:#1d4ed8;" onclick="posOpenPhotoModal(${item.id},'${safeName}')" title="Photo"><i class="bi bi-camera-fill"></i>Photo</button>`;
        const delBtn = pendingDeleteIds.has(item.id)
          ? `<div style="display:inline-flex;align-items:center;gap:3px;"><button class="btn-menu-action" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a!important;" onclick="openCancelRemovalModal(${item.id})"><i class="bi bi-hourglass-split"></i>Pending</button><button class="btn-menu-action" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5!important;" onclick="openCancelRemovalModal(${item.id})"><i class="bi bi-x-circle"></i>Cancel</button></div>`
          : `<button class="btn-menu-action" style="background:#f8d7da;color:#842029;" onclick="deleteMenuItem(${item.id})"><i class="bi bi-trash-fill"></i>Delete</button>`;
        return `<tr>
          <td style="white-space:nowrap;">${img}<span style="font-weight:600;vertical-align:middle;">${item.name}</span></td>
          <td style="white-space:nowrap;"><span class="badge-cat">${item.category}</span></td>
          <td style="font-weight:600;color:var(--cafe-amber);white-space:nowrap;">₱${item.price}</td>
          <td style="white-space:nowrap;">
            <span class="stock-badge ${sc}" style="font-size:10.5px;padding:2px 7px;">${si}${st}</span>
            <div style="margin-top:4px;display:flex;align-items:center;gap:3px;">
              <button class="qty-btn" onclick="adjustStock(${item.id},-1)">−</button>
              <input type="number" min="0" value="${item.stock}" style="width:42px;height:24px;font-size:11.5px;font-weight:700;border:1px solid var(--cafe-tan);border-radius:5px;padding:0 2px;text-align:center;" onchange="setStock(${item.id},this.value)">
              <button class="qty-btn" onclick="adjustStock(${item.id},1)">+</button>
            </div></td>
          <td style="white-space:nowrap;"><span class="${item.available?'badge-success':'badge-pending'}" style="font-size:11px;padding:3px 8px;">${item.available?'Available':'Unavailable'}</span></td>
          <td style="white-space:nowrap;text-align:right;">${avBtn}${photoBtn}${delBtn}</td>
        </tr>`;
      }).join('');
    }

JSEOF;

$s = substr($s, 0, $p1) . $newJS . substr($s, $p2);
echo "JS block replaced cleanly without invalid escape\n";

// ── 2. Fix buildMenuDropdowns() call in loadMenuFromDB ──
// Remove erroneous early call on line 9804
$s = str_replace(
    "          buildMenuDropdowns();\n          pendingDeleteIds = new Set((res.pending_deletions || []).map(Number));",
    "          pendingDeleteIds = new Set((res.pending_deletions || []).map(Number));",
    $s
);

// Call buildMenuDropdowns() AFTER menuItems is mapped
$targetEndMap = "              stock: parseInt(i.stock), minStock: parseInt(i.min_stock)\n            };\n          });";
$replaceEndMap = "              stock: parseInt(i.stock), minStock: parseInt(i.min_stock)\n            };\n          });\n          buildMenuDropdowns();";

if (strpos($s, $targetEndMap) !== false) {
    $s = str_replace($targetEndMap, $replaceEndMap, $s);
    echo "buildMenuDropdowns() placed right after menuItems mapping\n";
} else {
    echo "WARNING: targetEndMap not found\n";
}

// ── 3. Write back ──
$out = str_replace("\n", "\r\n", $s);
file_put_contents($file, $out);
echo "File updated successfully.\n";
