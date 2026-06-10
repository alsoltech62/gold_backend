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

if (!isset($data['months']) || !isset($data['gold_grams'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$months = intval($data['months']);
$grams = floatval($data['gold_grams']);

if ($grams <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get balance
$stmt = $conn->prepare("SELECT 
    COALESCE(SUM(CASE WHEN t.type = 'buy' AND t.status = 'completed' AND (t.metal_type = 'gold' OR t.metal_type IS NULL) THEN t.gold_grams ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN t.type IN ('sell', 'delivery') AND t.status = 'completed' AND (t.metal_type = 'gold' OR t.metal_type IS NULL) THEN t.gold_grams ELSE 0 END), 0) as total_gold
    FROM transactions t WHERE t.user_id = ?");
$stmt->execute([$user['id']]);
$balance = $stmt->fetch(PDO::FETCH_ASSOC)['total_gold'] ?? 0;

if ($grams > $balance) {
    echo json_encode(['success' => false, 'message' => 'Insufficient gold balance']);
    exit;
}

// Get Plan ID
$stmt = $conn->prepare("SELECT id FROM lock_in_plans WHERE months = ? AND status = 'active'");
$stmt->execute([$months]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    echo json_encode(['success' => false, 'message' => 'Invalid lock-in plan']);
    exit;
}

try {
    $conn->beginTransaction();

    $end_date = date('Y-m-d H:i:s', strtotime("+$months months"));
    
    $stmt = $conn->prepare("INSERT INTO user_lock_ins (user_id, plan_id, gold_grams, start_date, end_date, status) 
                            VALUES (?, ?, ?, NOW(), ?, 'active')");
    $stmt->execute([$user['id'], $plan['id'], $grams, $end_date]);

    // Add a transaction representing lock
    $desc = "Locked $grams g for $months months";
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, metal_type, gold_grams, amount_inr, status, notes) 
                            VALUES (?, 'sell', 'gold', ?, 0, 'completed', ?)");
    $stmt->execute([$user['id'], $grams, $desc]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Gold locked successfully']);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to lock gold: ' . $e->getMessage()]);
}
