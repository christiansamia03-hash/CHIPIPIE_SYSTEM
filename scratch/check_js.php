<?php
$html = file_get_contents('chipipie-pos.php');

// Find all script tags
preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $matches);

echo "Found " . count($matches[0]) . " script tags.\n";

foreach ($matches[1] as $idx => $js) {
    if (trim($js) === '') continue;
    $lines = explode("\n", $js);
    echo "Script tag #$idx: " . count($lines) . " lines.\n";
    // Check balanced braces and parenthesis in JS
    $openBraces = substr_count($js, '{');
    $closeBraces = substr_count($js, '}');
    $openParens = substr_count($js, '(');
    $closeParens = substr_count($js, ')');
    $openBrackets = substr_count($js, '[');
    $closeBrackets = substr_count($js, ']');
    echo "  Braces: $openBraces open, $closeBraces close " . ($openBraces !== $closeBraces ? "MISMATCH!" : "OK") . "\n";
    echo "  Parens: $openParens open, $closeParens close " . ($openParens !== $closeParens ? "MISMATCH!" : "OK") . "\n";
    echo "  Brackets: $openBrackets open, $closeBrackets close " . ($openBrackets !== $closeBrackets ? "MISMATCH!" : "OK") . "\n";
}
