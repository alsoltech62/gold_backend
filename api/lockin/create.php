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

if (!isset($data['months']) || (!isset($data['gold_grams']) && !isset($data['grams']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$months = intval($data['months']);
$grams = floatval($data['grams'] ?? $data['gold_grams']);
$metal_type = isset($data['metal_type']) ? $data['metal_type'] : 'gold';

if ($grams <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

if (!in_array($metal_type, ['gold', 'silver'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid metal type']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get balance
// Get balance
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

// Get Plan ID
// Get Plan ID
$stmt = $conn->prepare("SELECT id FROM lock_in_plans WHERE months = ? AND metal_type = ? AND status = 'active'");
$stmt->execute([$months, $metal_type]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    echo json_encode(['success' => false, 'message' => "Invalid $metal_type lock-in plan"]);
    exit;
}

try {
    $conn->beginTransaction();

    $end_date = date('Y-m-d H:i:s', strtotime("+$months months"));
    
    $gold_grams = $metal_type === 'gold' ? $grams : 0;
    $silver_grams = $metal_type === 'silver' ? $grams : 0;
    
    $stmt = $conn->prepare("INSERT INTO user_lock_ins (user_id, plan_id, gold_grams, silver_grams, metal_type, start_date, end_date, status) 
                            VALUES (?, ?, ?, ?, ?, NOW(), ?, 'active')");
    $stmt->execute([$user['id'], $plan['id'], $gold_grams, $silver_grams, $metal_type, $end_date]);

    // Add a transaction representing lock
    $desc = "Locked $grams g of $metal_type for $months months";
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, metal_type, gold_grams, amount_inr, status, notes) 
                            VALUES (?, 'sell', ?, ?, 0, 'completed', ?)");
    $stmt->execute([$user['id'], $metal_type, $grams, $desc]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => ucfirst($metal_type) . ' locked successfully']);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Failed to lock $metal_type: " . $e->getMessage()]);
}
