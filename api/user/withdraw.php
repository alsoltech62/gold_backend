<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$amount = floatval($data['amount'] ?? 0);

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

try {
    $db->beginTransaction();

    // Check balance and bank details
    $stmt = $db->prepare("SELECT inr_wallet, bank_name, account_number, ifsc_code, account_holder_name FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$user['id']]);
    $u = $stmt->fetch();

    if (!$u || $u['inr_wallet'] < $amount) {
        throw new Exception('Insufficient INR balance');
    }

    if (empty($u['bank_name']) || empty($u['account_number']) || empty($u['ifsc_code'])) {
        throw new Exception('Bank account details not found. Please update your profile.');
    }

    // Deduct from wallet
    $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ? WHERE id = ?")->execute([$amount, $user['id']]);

    // Create withdrawal request
    $stmt = $db->prepare("INSERT INTO withdrawal_requests (user_id, amount, bank_name, account_number, ifsc_code, account_holder_name, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $user['id'], $amount, $u['bank_name'], $u['account_number'], $u['ifsc_code'], $u['account_holder_name']
    ]);

    // Add transaction history
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, status, transaction_source, notes, description) VALUES (?, 'sell', ?, 'pending', 'wallet', 'Withdrawal Request', 'Funds withdrawal to bank account')");
    $stmt->execute([$user['id'], $amount]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Withdrawal request submitted successfully']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
