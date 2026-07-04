<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

// --- Configuration ---
// Adjust this URL to wherever your Japsan Coin Ecosystem API is running
$jc_api_url = 'https://odofast.in/api/external/wallet_api.php'; 
$secret = 'JAPSAN_EXTERNAL_API_SECRET_2026';

// Initialize cURL
$ch = curl_init();
$url = $jc_api_url . '?action=get_balance&mobile=' . urlencode($user['mobile']) . '&secret=' . urlencode($secret);

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// Optional: Disable SSL verification if testing locally without valid certificates
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response && $http_code == 200) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success']) {
        $external_balance = $data['balance'];
        
        // Update local japsan_wallet in Gold Platform
        $db->prepare("UPDATE users SET japsan_wallet = ? WHERE id = ?")->execute([$external_balance, $user['id']]);
        
        echo json_encode(['success' => true, 'japsan_wallet' => $external_balance, 'message' => 'Linked and synced successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $data['message'] ?? 'Failed to parse Japsan Coin data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Could not connect to Japsan Coin Ecosystem']);
}
