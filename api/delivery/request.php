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

if (!isset($data['gold_grams']) && !isset($data['grams']) || !isset($data['delivery_address'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$grams = floatval($data['grams'] ?? $data['gold_grams']);
$address = htmlspecialchars(strip_tags($data['delivery_address']));
$metal_type = isset($data['metal_type']) ? $data['metal_type'] : 'gold';

if ($grams < 1) {
    echo json_encode(['success' => false, 'message' => 'Minimum 1 gram required']);
    exit;
}

if (!in_array($metal_type, ['gold', 'silver'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid metal type']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Calculate total metal
$stmt = $conn->prepare("SELECT 
    COALESCE(SUM(CASE WHEN t.type = 'buy' AND t.status = 'completed' AND t.metal_type = ? THEN t.gold_grams ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN t.type IN ('sell', 'delivery') AND t.status = 'completed' AND t.metal_type = ? THEN t.gold_grams ELSE 0 END), 0) as total_metal
    FROM transactions t WHERE t.user_id = ?");
$stmt->execute([$metal_type, $metal_type, $user['id']]);
$balance = $stmt->fetch(PDO::FETCH_ASSOC)['total_metal'] ?? 0;

if ($grams > $balance) {
    echo json_encode(['success' => false, 'message' => "Insufficient $metal_type balance"]);
    exit;
}

// Fetch Delivery Charges from Settings
$stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('delivery_charge', 'package_charge', 'forwarding_charge')");
$charges = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $charges[$row['setting_key']] = floatval($row['setting_value']);
}

$deliveryCharge = $charges['delivery_charge'] ?? 150;
$packageCharge = $charges['package_charge'] ?? 50;
$forwardingCharge = $charges['forwarding_charge'] ?? 100;
$totalCharge = $deliveryCharge + $packageCharge + $forwardingCharge;

try {
    $conn->beginTransaction();

    // Deduct charge from wallet or record it
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, metal_type, gold_grams, amount_inr, status, description) 
                            VALUES (?, 'delivery', ?, ?, ?, 'pending', ?)");
    $desc = "Delivery request to " . $address . " (Charges: ₹" . $totalCharge . ")";
    $stmt->execute([$user['id'], $metal_type, $grams, $totalCharge, $desc]);
    $transaction_id = $conn->lastInsertId();

    // Insert into delivery_requests table
    $stmt = $conn->prepare("INSERT INTO delivery_requests (user_id, transaction_id, metal_type, gold_grams, total_cost, delivery_address, delivery_city, delivery_state, delivery_pincode, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$user['id'], $transaction_id, $metal_type, $grams, $totalCharge, $data['delivery_address'], $data['city'] ?? '', $data['state'] ?? '', $data['pincode'] ?? '']);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Delivery request submitted. Charges: ₹' . $totalCharge]);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to request delivery']);
}
