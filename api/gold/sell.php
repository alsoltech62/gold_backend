<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$user = authenticate();
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);
if (empty($data)) {
    $data = $_POST;
}
$gold_grams = (float)($data['gold_grams'] ?? 0);

if ($gold_grams <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid gold amount']); exit(); }

$db = (new Database())->getConnection();
$summary = $db->prepare("SELECT total_gold_grams FROM user_gold_summary WHERE user_id=?");
$summary->execute([$user['id']]);
$balance = $summary->fetch();

if (!$balance || $balance['total_gold_grams'] < $gold_grams) {
    echo json_encode(['success' => false, 'message' => 'Insufficient gold balance']); exit();
}

$rate = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1")->fetch();
$amount_inr = round($gold_grams * $rate['rate_per_gram'], 2);

$stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, status) VALUES (?, 'sell', ?, ?, ?, 'pending')");
$stmt->execute([$user['id'], $amount_inr, $gold_grams, $rate['rate_per_gram']]);
$txn_id = $db->lastInsertId();

$db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Sell Request Submitted', ?, 'transaction')")
   ->execute([$user['id'], "Sell request for {$gold_grams}g (≈₹{$amount_inr}) is under review"]);

require_once __DIR__ . '/../../api/firebase_helper.php';
$uStmt = $db->prepare("SELECT fcm_token FROM users WHERE id = ?");
$uStmt->execute([$user['id']]);
$fcm_token = $uStmt->fetchColumn();
if ($fcm_token) {
    sendFCMNotification($fcm_token, 'Sell Request Submitted', "Sell request for {$gold_grams}g (≈₹{$amount_inr}) is under review");
}

echo json_encode(['success' => true, 'message' => 'Sell request submitted', 'data' => [
    'transaction_id' => $txn_id, 'gold_grams' => $gold_grams, 'estimated_amount' => $amount_inr
]]);
