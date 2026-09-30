<?php
$f = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$lines = explode("\n", file_get_contents($f));
foreach ($lines as $i => $l) {
    if (preg_match('/\.(prod-name|prod-price|prod-emoji|stock-badge)/', $l) && strpos($l, '{') !== false && stripos($l, 'pizza') === false) {
        echo ($i+1) . ": " . trim($l) . "\n";
    }
}
