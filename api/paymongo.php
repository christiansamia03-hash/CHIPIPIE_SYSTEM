<?php
/**
 * ChipiPie Cafe — PayMongo Proxy
 *
 * POST ?action=create_qrph              — Create a dynamic QR PH via Payment Intents API
 * GET  ?action=check_qrph&id=PI_ID     — Poll Payment Intent for QR PH payment status
 * POST ?action=create_link              — Create a PayMongo Payment Link (fallback)
 * GET  ?action=check_link&id=LINK_ID   — Poll Payment Link status
 * GET  ?action=get_key                 — Return whether a secret key is configured
 * POST ?action=save_key                — Save the secret key (admin only)
 *
 * QR PH Flow (correct PayMongo Payment Intents approach):
 *   1. POST /v1/payment_intents  → get payment_intent_id + client_key
 *   2. POST /v1/payment_methods  { type: qrph } → get payment_method_id
 *   3. POST /v1/payment_intents/{id}/attach { payment_method, client_key }
 *      → response next_action.code.image_url = base64 QR PH image (scannable)
 *   4. Poll GET /v1/payment_intents/{id} until status === 'succeeded'
 */
require_once __DIR__ . '/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($action === 'create_link' && $method === 'POST') {
    createPaymentLink();
} elseif ($action === 'check_link' && $method === 'GET') {
    checkPaymentLink();
} elseif ($action === 'create_qrph' && $method === 'POST') {
    createQRPH();
} elseif ($action === 'check_qrph' && $method === 'GET') {
    checkQRPH();
} elseif ($action === 'get_key' && $method === 'GET') {
    getKeyStatus();
} elseif ($action === 'save_key' && $method === 'POST') {
    saveKey();
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
}

/* ========== KEY MANAGEMENT ========== */

function getPaymongoKey(): string {
    return trim(getSetting('paymongo_secret_key') ?? '');
}

function getKeyStatus(): void {
    requireLogin();
    session_write_close();
    $key = getPaymongoKey();
    jsonResponse(['success' => true, 'configured' => !empty($key)]);
}

function saveKey(): void {
    requireLogin();
    session_write_close();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Only admins can save API keys.'], 403);
    }
    $input = getJsonInput();
    $key = trim($input['secret_key'] ?? '');
    if (empty($key)) {
        jsonResponse(['success' => false, 'message' => 'Secret key cannot be empty.'], 400);
    }
    // Validate key starts with sk_
    if (!str_starts_with($key, 'sk_')) {
        jsonResponse(['success' => false, 'message' => 'Invalid key format. Must start with sk_test_ or sk_live_.'], 400);
    }
    $db = getDB();
    $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('paymongo_secret_key', ?)
                  ON DUPLICATE KEY UPDATE setting_value = ?")
       ->execute([$key, $key]);
    jsonResponse(['success' => true, 'message' => 'PayMongo key saved.']);
}

/* ========== CREATE PAYMENT LINK ========== */

function createPaymentLink(): void {
    requireLogin();
    session_write_close();

    $input = getJsonInput();
    $amountPHP = floatval($input['amount'] ?? 0);
    $description = trim($input['description'] ?? 'ChipiPie Order');
    $orderRef = trim($input['order_ref'] ?? '');

    if ($amountPHP <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid amount.'], 400);
    }

    $key = getPaymongoKey();
    if (empty($key)) {
        jsonResponse(['success' => false, 'message' => 'PayMongo secret key not configured. Please set it in Settings.'], 400);
    }

    // PayMongo Payment Links — create a standard checkout link
    $payload = [
        'data' => [
            'attributes' => [
                'amount'      => $amountCentavos,
                'description' => $description,
                'remarks'     => $orderRef ?: 'ChipiPie Cafe Order',
            ]
        ]
    ];

    $ch = curl_init('https://api.paymongo.com/v1/links');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($key . ':'),
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        jsonResponse(['success' => false, 'message' => 'Network error: ' . $curlErr], 500);
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 && $httpCode !== 201) {
        $errDetail = $data['errors'][0]['detail'] ?? null;
        $errCode   = $data['errors'][0]['code']   ?? null;
        $errMsg    = $errDetail ?: ($data['message'] ?? ('HTTP ' . $httpCode));
        if ($errCode) $errMsg = "[$errCode] $errMsg";
        jsonResponse(['success' => false, 'message' => $errMsg, 'raw' => $response], 502);
    }

    $attr    = $data['data']['attributes'] ?? [];
    $linkId  = $data['data']['id'] ?? '';
    $linkUrl = $attr['checkout_url'] ?? '';

    if (!$linkUrl) {
        jsonResponse(['success' => false, 'message' => 'No checkout URL returned by PayMongo.'], 500);
    }

    // Fetch QR code server-side and return as base64 — no frontend library needed
    $qrBase64 = '';
    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($linkUrl) . '&format=png&margin=4';
    $qrCh = curl_init($qrApiUrl);
    curl_setopt_array($qrCh, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $qrData = curl_exec($qrCh);
    curl_close($qrCh);
    if ($qrData && substr($qrData, 0, 4) === "\x89PNG") {
        $qrBase64 = 'data:image/png;base64,' . base64_encode($qrData);
    }

    jsonResponse([
        'success'      => true,
        'link_id'      => $linkId,
        'checkout_url' => $linkUrl,
        'amount_php'   => $amountPHP,
        'qr_image'     => $qrBase64,
    ]);
}

/* ========== CHECK PAYMENT LINK STATUS ========== */

function checkPaymentLink(): void {
    requireLogin();
    session_write_close();

    $linkId = trim($_GET['id'] ?? '');
    if (empty($linkId)) {
        jsonResponse(['success' => false, 'message' => 'Missing link ID.'], 400);
    }

    $key = getPaymongoKey();
    if (empty($key)) {
        jsonResponse(['success' => false, 'message' => 'PayMongo not configured.'], 400);
    }

    $ch = curl_init("https://api.paymongo.com/v1/links/{$linkId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($key . ':'),
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        jsonResponse(['success' => false, 'message' => 'Network error.'], 500);
    }

    $data = json_decode($response, true);
    if ($httpCode !== 200) {
        jsonResponse(['success' => false, 'message' => 'Could not retrieve link status.'], 502);
    }

    $attr   = $data['data']['attributes'] ?? [];
    $status = $attr['status'] ?? 'unpaid';  // 'unpaid' | 'paid' | 'expired'

    jsonResponse([
        'success' => true,
        'status'  => $status,
        'paid'    => $status === 'paid',
    ]);
}

/* ========== DYNAMIC QR PH via Payment Intents ========== */

function createQRPH(): void {
    requireLogin();
    session_write_close();

    $input      = getJsonInput();
    $amountPHP  = floatval($input['amount'] ?? 0);
    $description = trim($input['description'] ?? 'ChipiPie Order');

    if ($amountPHP <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid amount.'], 400);
    }

    $key = getPaymongoKey();
    if (empty($key)) {
        jsonResponse(['success' => false, 'message' => 'PayMongo secret key not configured.'], 400);
    }

    $amountCentavos = intval(round($amountPHP * 100));
    if ($amountCentavos < 100) {
        jsonResponse(['success' => false, 'message' => 'Minimum amount is ₱1.00.'], 400);
    }

    $authHeader = 'Authorization: Basic ' . base64_encode($key . ':');
    $jsonHeader = ['Content-Type: application/json', 'Accept: application/json', $authHeader];

    /* ── STEP 1: Create Payment Intent with qrph allowed ── */
    $intentPayload = [
        'data' => [
            'attributes' => [
                'amount'                 => $amountCentavos,
                'currency'               => 'PHP',
                'payment_method_allowed' => ['qrph'],
                'description'            => $description,
                'capture_type'           => 'automatic',
            ]
        ]
    ];
    $ch1 = curl_init('https://api.paymongo.com/v1/payment_intents');
    curl_setopt_array($ch1, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($intentPayload),
        CURLOPT_HTTPHEADER     => $jsonHeader,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $r1 = curl_exec($ch1);
    $c1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
    $e1 = curl_error($ch1);
    curl_close($ch1);

    if ($e1) jsonResponse(['success' => false, 'message' => 'Network error (intent): ' . $e1], 500);
    $d1 = json_decode($r1, true);
    if ($c1 < 200 || $c1 >= 300) {
        $errMsg = $d1['errors'][0]['detail'] ?? ($d1['message'] ?? ('HTTP ' . $c1));
        if (!empty($d1['errors'][0]['code'])) $errMsg = '[' . $d1['errors'][0]['code'] . '] ' . $errMsg;
        jsonResponse(['success' => false, 'message' => $errMsg, 'raw' => $r1], 502);
    }

    $intentId  = $d1['data']['id'] ?? '';
    $clientKey = $d1['data']['attributes']['client_key'] ?? '';
    if (!$intentId) {
        jsonResponse(['success' => false, 'message' => 'No payment intent ID returned.'], 500);
    }

    /* ── STEP 2: Create Payment Method of type qrph ── */
    $pmPayload = ['data' => ['attributes' => ['type' => 'qrph']]];
    $ch2 = curl_init('https://api.paymongo.com/v1/payment_methods');
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($pmPayload),
        CURLOPT_HTTPHEADER     => $jsonHeader,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $r2 = curl_exec($ch2);
    $c2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    $e2 = curl_error($ch2);
    curl_close($ch2);

    if ($e2) jsonResponse(['success' => false, 'message' => 'Network error (payment method): ' . $e2], 500);
    $d2 = json_decode($r2, true);
    if ($c2 < 200 || $c2 >= 300) {
        $errMsg = $d2['errors'][0]['detail'] ?? ($d2['message'] ?? ('HTTP ' . $c2));
        jsonResponse(['success' => false, 'message' => $errMsg, 'raw' => $r2], 502);
    }

    $paymentMethodId = $d2['data']['id'] ?? '';
    if (!$paymentMethodId) {
        jsonResponse(['success' => false, 'message' => 'No payment method ID returned.'], 500);
    }

    /* ── STEP 3: Attach Payment Method → receive QR PH image ── */
    $attachPayload = [
        'data' => [
            'attributes' => [
                'payment_method' => $paymentMethodId,
                'client_key'     => $clientKey,
                'return_url'     => 'https://chipipiecafe.com/payment-done',
            ]
        ]
    ];
    $ch3 = curl_init("https://api.paymongo.com/v1/payment_intents/{$intentId}/attach");
    curl_setopt_array($ch3, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($attachPayload),
        CURLOPT_HTTPHEADER     => $jsonHeader,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $r3 = curl_exec($ch3);
    $c3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
    $e3 = curl_error($ch3);
    curl_close($ch3);

    if ($e3) jsonResponse(['success' => false, 'message' => 'Network error (attach): ' . $e3], 500);
    $d3 = json_decode($r3, true);
    if ($c3 < 200 || $c3 >= 300) {
        $errMsg = $d3['errors'][0]['detail'] ?? ($d3['message'] ?? ('HTTP ' . $c3));
        jsonResponse(['success' => false, 'message' => $errMsg, 'raw' => $r3], 502);
    }

    // Status should now be 'awaiting_next_action'
    // QR PH image is in next_action.code.image_url (base64 PNG)
    $attr3   = $d3['data']['attributes'] ?? [];
    $qrImage = $attr3['next_action']['code']['image_url'] ?? '';

    // Ensure data URI prefix
    if (!empty($qrImage) && !str_starts_with($qrImage, 'data:')) {
        $qrImage = 'data:image/png;base64,' . $qrImage;
    }

    jsonResponse([
        'success'    => true,
        'qrph_id'    => $intentId,   // Payment Intent ID — used for polling
        'qr_image'   => $qrImage,    // Scannable QR PH image (GCash/Maya/banks)
        'amount_php' => $amountPHP,
    ]);
}

function checkQRPH(): void {
    requireLogin();
    session_write_close();

    $intentId = trim($_GET['id'] ?? '');
    if (empty($intentId)) {
        jsonResponse(['success' => false, 'message' => 'Missing Payment Intent ID.'], 400);
    }

    $key = getPaymongoKey();
    if (empty($key)) {
        jsonResponse(['success' => false, 'message' => 'PayMongo not configured.'], 400);
    }

    $ch = curl_init("https://api.paymongo.com/v1/payment_intents/{$intentId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($key . ':'),
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $attr = $data['data']['attributes'] ?? [];
    // Statuses: awaiting_payment_method | awaiting_next_action | processing | succeeded | timed_out
    $status = $attr['status'] ?? 'awaiting_next_action';

    $paid    = $status === 'succeeded';
    $expired = $status === 'timed_out';

    jsonResponse([
        'success' => true,
        'status'  => $paid ? 'paid' : ($expired ? 'expired' : 'unpaid'),
        'paid'    => $paid,
    ]);
}
