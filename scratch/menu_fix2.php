<?php
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$src  = file_get_contents($file);
$s    = str_replace("\r\n", "\n", $src);

// Fix 1: Remove overflow:hidden from menu-table-card (it blocks horizontal scroll)
$s = str_replace(
    "    .menu-table-card {\n      padding: 14px 16px;\n      overflow: hidden;\n    }",
    "    .menu-table-card {\n      padding: 14px 16px;\n      overflow: visible;\n    }\n    .menu-branch-body .menu-table-card {\n      border-top-left-radius: 0 !important;\n      border-top-right-radius: 0 !important;\n      border-top: none !important;\n      margin-top: -1px;\n    }",
    $s
);

// Fix 2: Replace &#x20B1; with the actual rupee/peso UTF-8 char in the JS template
$s = str_replace('&#x20B1;${item.price}', '₱${item.price}', $s);

// Fix 3: Replace &minus; with literal − in the JS template
$s = str_replace('>&minus;</button>', '>−</button>', $s);

$out = str_replace("\n", "\r\n", $s);
$bytes = file_put_contents($file, $out);
echo "Written: $bytes bytes\n";
