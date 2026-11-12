<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);
$s    = str_replace("\r\n", "\n", $src);

// Add the missing accordion CSS right before .menu-table-card
$target = "    .menu-table-card {";
$insert = "    /* ── Menu branch accordion ── */
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
    ";

if (strpos($s, '.menu-branch-header {') !== false) {
    echo "Accordion CSS already present\n";
} else {
    $s = str_replace($target, $insert . $target, $s);
    echo "Accordion CSS added\n";
}

// Also remove the old .menu-mgmt-tabs-scroll CSS that's still there (cosmetic, doesn't hurt but cleans up)
// Keep it in case it's referenced elsewhere

$out = str_replace("\n", "\r\n", $s);
$bytes = file_put_contents($file, $out);
echo "Written: $bytes bytes\n";
