<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$user = authenticate();
$data = json_decode(file_get_contents('php://input'), true);
$grams = (float)($data['grams'] ?? 0);

if ($grams <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit();
}

$db = (new Database())->getConnection();

// Check user silver balance
$summary = $db->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN t.type = 'buy' AND t.status = 'completed' AND t.metal_type = 'silver' THEN t.gold_grams ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN t.type IN ('sell', 'delivery') AND t.status = 'completed' AND t.metal_type = 'silver' THEN t.gold_grams ELSE 0 END), 0) as total_silver_grams
    FROM transactions t
    WHERE t.user_id = ?
");
$summary->execute([$user['id']]);
$silver_bal = $summary->fetch();
$total_silver = (float)($silver_bal['total_silver_grams'] ?? 0);

if ($grams > $total_silver) {
    echo json_encode(['success' => false, 'message' => 'Insufficient silver balance']);
    exit();
}

$rate = $db->query("SELECT rate_per_gram FROM silver_rates ORDER BY rate_date DESC LIMIT 1")->fetch();
if (!$rate) { echo json_encode(['success' => false, 'message' => 'Silver rate not available']); exit(); }

$amount_inr = round($grams * $rate['rate_per_gram'], 2);

$stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status) VALUES (?, 'sell', ?, ?, ?, 'silver', 'completed')");
$stmt->execute([$user['id'], $amount_inr, $grams, $rate['rate_per_gram']]);

$db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Silver Sold Successful', ?, 'transaction')")
   ->execute([$user['id'], "You sold {$grams}g silver for ₹{$amount_inr}"]);

require_once __DIR__ . '/../../api/firebase_helper.php';
$uStmt = $db->prepare("SELECT fcm_token FROM users WHERE id = ?");
$uStmt->execute([$user['id']]);
$fcm_token = $uStmt->fetchColumn();
if ($fcm_token) {
    sendFCMNotification($fcm_token, 'Silver Sold Successful', "You sold {$grams}g silver for ₹{$amount_inr}");
}

echo json_encode(['success' => true, 'message' => 'Silver sold successfully', 'data' => [
    'amount_inr' => $amount_inr
]]);
