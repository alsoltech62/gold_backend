<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user = authenticate();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['amount']) || !isset($data['frequency'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$amount = floatval($data['amount']);
$frequency = in_array($data['frequency'], ['daily', 'weekly', 'monthly']) ? $data['frequency'] : 'monthly';

if ($amount < 500) {
    echo json_encode(['success' => false, 'message' => 'Minimum SIP amount is ₹500']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("UPDATE users SET sip_active = 1, sip_amount = ?, sip_frequency = ? WHERE id = ?");

if ($stmt->execute([$amount, $frequency, $user['id']])) {
    echo json_encode(['success' => true, 'message' => 'SIP Setup successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to setup SIP']);
}
