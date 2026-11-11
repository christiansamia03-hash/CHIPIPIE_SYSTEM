<?php
function testLogin($user, $pass, $portal = 'pos') {
    echo "--- Testing Login for '$user' on portal '$portal' ---\n";
    $url = "http://localhost/CHIPIPIE_SYSTEM/api/auth.php?action=login";
    $data = json_encode(['username' => $user, 'password' => $pass]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-Portal: $portal"
    ]);
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n\n";
    return json_decode($response, true);
}

testLogin('admin', 'Admin123', 'pos');
testLogin('cashier1', 'cashier123', 'pos');
