<?php
/**
 * Vercel Serverless Entrypoint / Router for ChipiPie POS
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (strpos($uri, 'superadmin') !== false) {
    require __DIR__ . '/../superadmin-panel.php';
} else {
    require __DIR__ . '/../chipipie-pos.php';
}
