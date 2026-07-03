<?php
require_once '../../middleware/auth.php';
require_once '../../config/razorpay.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$user = authenticate();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$amount_inr = isset($data['amount_inr']) ? floatval($data['amount_inr']) : 0;

if ($amount_inr < 100) {
    echo json_encode(['success' => false, 'message' => 'Minimum investment is ₹100']);
    exit;
}

$receipt = 'rcptid_' . time() . '_' . $user['id'];

// Create Razorpay Order via cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'amount' => round($amount_inr * 100), // in paise
    'currency' => 'INR',
    'receipt' => $receipt
]));
curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$orderData = json_decode($response, true);

if ($http_status === 200 && isset($orderData['id'])) {
    echo json_encode([
        'success' => true,
        'data' => [
            'order_id' => $orderData['id'],
            'amount' => $orderData['amount'],
            'key' => RAZORPAY_KEY_ID
        ]
    ]);
} else {
    error_log('Razorpay Order Failed: ' . print_r($orderData, true));
    echo json_encode(['success' => false, 'message' => 'Failed to create payment order', 'error' => $orderData]);
}
?>
