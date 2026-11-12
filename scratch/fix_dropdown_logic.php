<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);
$s    = str_replace("\r\n", "\n", $src);

// 1. Move POS_PIZZA_CAT_IDS above buildMenuDropdowns
$oldTop = "    /* ========== MENU TABLE ========== */\n\n    let currentMenuFilter = null; // null=all, string=category name\n\n    function buildMenuDropdowns() {\n      if (!window.posMenuCategories || !posMenuCategories.length) return;";
$newTop = "    /* ========== MENU TABLE ========== */\n\n    const POS_PIZZA_CAT_IDS = new Set([10, 11, 12, 13, 14, 15, 16]);\n    let currentMenuFilter = null; // null=all, string=category name\n\n    function buildMenuDropdowns() {\n      if (!posMenuCategories || !posMenuCategories.length) return;";

if (strpos($s, $oldTop) !== false) {
    $s = str_replace($oldTop, $newTop, $s);
    echo "Moved POS_PIZZA_CAT_IDS to top of menu table section and fixed check\n";
} else {
    echo "ERROR: oldTop not found\n";
    exit(1);
}

// 2. Remove duplicate POS_PIZZA_CAT_IDS at old location
$oldDef = "    /* --- Populate Add-Item category dropdown dynamically --- */\n    const POS_PIZZA_CAT_IDS = new Set([10, 11, 12, 13, 14, 15, 16]);";
$newDef = "    /* --- Populate Add-Item category dropdown dynamically --- */";

if (strpos($s, $oldDef) !== false) {
    $s = str_replace($oldDef, $newDef, $s);
    echo "Removed duplicate definition from lower section\n";
} else {
    echo "WARNING: oldDef not found\n";
}

$out = str_replace("\n", "\r\n", $s);
file_put_contents($file, $out);
echo "Done.\n";
