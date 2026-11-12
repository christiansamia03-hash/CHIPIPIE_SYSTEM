<?php
// Extract the main <script> block and check for the toggleMenuSection function
$file = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/chipipie-pos.php';
$html = file_get_contents($file);

// Find the main app script block (last large script block)
preg_match_all('/<script>(.*?)<\/script>/s', $html, $m);
echo "Total script blocks: " . count($m[1]) . "\n";

// Find specifically toggleMenuSection
$pos = strpos($html, 'function toggleMenuSection');
if ($pos !== false) {
    // Count line number
    $line = substr_count(substr($html, 0, $pos), "\n") + 1;
    echo "toggleMenuSection found at line: $line\n";
    echo substr($html, $pos, 300) . "\n";
} else {
    echo "toggleMenuSection NOT FOUND in file!\n";
}

// Check if it's inside a nested function (check preceding 500 chars for 'function ')
$before = substr($html, max(0, $pos - 1000), 1000);
$funcMatches = [];
preg_match_all('/function\s+\w+\s*\(/', $before, $funcMatches);
echo "\nFunctions before toggleMenuSection (last 1000 chars):\n";
print_r($funcMatches[0]);
