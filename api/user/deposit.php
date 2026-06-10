<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $amount = (float)($data->amount ?? 0);
    $wallet_type = strtolower($data->wallet_type ?? 'inr');

    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }

    if (!in_array($wallet_type, ['inr', 'japsan'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid wallet type']);
        exit;
    }

    try {
        $db->beginTransaction();

        $column = $wallet_type === 'japsan' ? 'japsan_wallet' : 'inr_wallet';

        // Add to respective wallet
        $stmt = $db->prepare("UPDATE users SET {$column} = {$column} + ? WHERE id = ?");
        $stmt->execute([$amount, $user['id']]);

        // Record deposit as a transaction
        $currency_label = $wallet_type === 'japsan' ? 'Japsan Coin Deposit' : 'INR Deposit';
        $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, status, transaction_source, notes) VALUES (?, 'deposit', ?, 'completed', 'wallet', ?)");
        $stmt->execute([$user['id'], $amount, $currency_label]);

        // Referral logic: "utna rs ka silver milega as refferl bonce"
        $uStmt = $db->prepare("SELECT referred_by FROM users WHERE id = ?");
        $uStmt->execute([$user['id']]);
        $uData = $uStmt->fetch();
        if ($uData && $uData['referred_by']) {
            $referrer_id = $uData['referred_by'];
            
            // Get silver rate
            $sRateStmt = $db->query("SELECT rate_per_gram FROM silver_rates ORDER BY rate_date DESC LIMIT 1");
            $sRate = $sRateStmt->fetch();
            if ($sRate) {
                $silver_grams = $amount / $sRate['rate_per_gram'];
                
                $db->prepare("UPDATE users SET silver_wallet = silver_wallet + ? WHERE id = ?")
                   ->execute([$silver_grams, $referrer_id]);
                   
                $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'buy', ?, ?, ?, 'silver', 'completed', 'referral', 'Referral bonus from user deposit')")
                   ->execute([$referrer_id, 0, $silver_grams, $sRate['rate_per_gram']]);
            }
        }

        // Check if INR wallet reached 1000, convert 1000 to Gold
        if ($wallet_type === 'inr') {
            $uStmt = $db->prepare("SELECT inr_wallet FROM users WHERE id = ?");
            $uStmt->execute([$user['id']]);
            $current_wallet = $uStmt->fetch()['inr_wallet'];

        if ($current_wallet >= 1000) {
            $gRateStmt = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1");
            $gRate = $gRateStmt->fetch();
            if ($gRate) {
                $gold_grams = 1000 / $gRate['rate_per_gram'];
                
                // Deduct 1000 from wallet
                $db->prepare("UPDATE users SET inr_wallet = inr_wallet - 1000 WHERE id = ?")->execute([$user['id']]);
                
                // Add gold purchase transaction
                $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'buy', 1000, ?, ?, 'gold', 'completed', 'wallet', 'Auto SIP conversion')")
                   ->execute([$user['id'], $gold_grams, $gRate['rate_per_gram']]);
            }
        }
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Deposit successful']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Deposit failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
}
