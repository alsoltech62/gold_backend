<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$user = authenticate();
$data = json_decode(file_get_contents('php://input'), true);
$amount_inr = (float)($data['amount_inr'] ?? 0);
$payment_method = $data['payment_method'] ?? 'UPI';
$payment_id = $data['payment_id'] ?? null;

if ($amount_inr < 100) {
    echo json_encode(['success' => false, 'message' => 'Minimum purchase amount is ₹100']);
    exit();
}

$db = (new Database())->getConnection();
$rate = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1")->fetch();
if (!$rate) { echo json_encode(['success' => false, 'message' => 'Gold rate not available']); exit(); }

$gold_grams = round($amount_inr / $rate['rate_per_gram'], 4);

try {
    $db->beginTransaction();

    if ($payment_method === 'inr_wallet') {
        $uStmt = $db->prepare("SELECT inr_wallet FROM users WHERE id = ?");
        $uStmt->execute([$user['id']]);
        $inr_wallet = $uStmt->fetch()['inr_wallet'];
        if ($inr_wallet < $amount_inr) {
            echo json_encode(['success' => false, 'message' => 'Insufficient INR wallet balance']); exit;
        }
        $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ? WHERE id = ?")->execute([$amount_inr, $user['id']]);
    } elseif ($payment_method === 'japsan_wallet') {
        $uStmt = $db->prepare("SELECT japsan_wallet FROM users WHERE id = ?");
        $uStmt->execute([$user['id']]);
        $japsan_wallet = $uStmt->fetch()['japsan_wallet'];
        if ($japsan_wallet < $amount_inr) {
            echo json_encode(['success' => false, 'message' => 'Insufficient Japsan coin balance']); exit;
        }
        $db->prepare("UPDATE users SET japsan_wallet = japsan_wallet - ? WHERE id = ?")->execute([$amount_inr, $user['id']]);
    } elseif ($payment_method === 'silver_wallet') {
        $sRate = $db->query("SELECT rate_per_gram FROM silver_rates ORDER BY rate_date DESC LIMIT 1")->fetch();
        if (!$sRate) { echo json_encode(['success' => false, 'message' => 'Silver rate not available']); exit; }
        $required_silver = round($amount_inr / $sRate['rate_per_gram'], 4);
        
        $uStmt = $db->prepare("SELECT total_silver_grams FROM user_summary WHERE user_id = ?");
        $uStmt->execute([$user['id']]);
        $summary = $uStmt->fetch();
        if (!$summary || $summary['total_silver_grams'] < $required_silver) {
            echo json_encode(['success' => false, 'message' => 'Insufficient Silver balance']); exit;
        }
        // Add transaction to deduct silver (sell)
        $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, payment_method, notes) VALUES (?, 'sell', ?, ?, ?, 'silver', 'completed', 'wallet', 'Sold to buy Gold')")
           ->execute([$user['id'], $amount_inr, $required_silver, $sRate['rate_per_gram']]);
    }

    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, payment_method, payment_id) VALUES (?, 'buy', ?, ?, ?, 'gold', 'completed', ?, ?)");
    $stmt->execute([$user['id'], $amount_inr, $gold_grams, $rate['rate_per_gram'], $payment_method, $payment_id]);
    $txn_id = $db->lastInsertId();

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    exit();
}

// Notification
$db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Gold Purchase Successful', ?, 'transaction')")
   ->execute([$user['id'], "You bought {$gold_grams}g gold for ₹{$amount_inr}"]);

require_once __DIR__ . '/../../api/firebase_helper.php';
$uStmt = $db->prepare("SELECT fcm_token FROM users WHERE id = ?");
$uStmt->execute([$user['id']]);
$fcm_token = $uStmt->fetchColumn();
if ($fcm_token) {
    sendFCMNotification($fcm_token, 'Gold Purchase Successful', "You bought {$gold_grams}g gold for ₹{$amount_inr}");
}

echo json_encode(['success' => true, 'message' => 'Gold purchased successfully', 'data' => [
    'transaction_id' => $txn_id, 'gold_grams' => $gold_grams,
    'amount_inr' => $amount_inr, 'gold_rate' => $rate['rate_per_gram']
]]);
