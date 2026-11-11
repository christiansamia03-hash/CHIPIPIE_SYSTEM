<?php
$html = file_get_contents('chipipie-pos.php');

preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $matches);

foreach ($matches[1] as $idx => $js) {
    echo "=== SCRIPT TAG #$idx ===\n";
    $lines = explode("\n", $js);
    $declared = [];
    $errors = [];
    foreach ($lines as $lineNum => $line) {
        $trimmed = trim($line);
        if (preg_match('/^(?:const|let)\s+([a-zA-Z0-9_$]+)\s*=/', $trimmed, $m)) {
            $varName = $m[1];
            if (isset($declared[$varName])) {
                $errors[] = "Duplicate declaration: '$varName' on line " . ($lineNum + 1) . " (prev line " . $declared[$varName] . ")";
            } else {
                $declared[$varName] = $lineNum + 1;
            }
        }
    }
    foreach ($errors as $e) echo "  $e\n";
    if (empty($errors)) echo "  OK.\n";
}
