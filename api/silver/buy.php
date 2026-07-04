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

if ($payment_method === 'UPI') {
    $razorpay_order_id = $data['razorpay_order_id'] ?? null;
    $razorpay_signature = $data['razorpay_signature'] ?? null;

    if ($razorpay_order_id && $razorpay_signature) {
        require_once __DIR__ . '/../../config/razorpay.php';
        $generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $payment_id, RAZORPAY_KEY_SECRET);

        if ($generated_signature !== $razorpay_signature) {
            echo json_encode(['success' => false, 'message' => 'Payment verification failed (Invalid Signature)']);
            exit();
        }
    }
}

$db = (new Database())->getConnection();
$rate = $db->query("SELECT rate_per_gram FROM silver_rates ORDER BY rate_date DESC LIMIT 1")->fetch();
if (!$rate) { echo json_encode(['success' => false, 'message' => 'Silver rate not available']); exit(); }

$silver_grams = round($amount_inr / $rate['rate_per_gram'], 4);

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
        // We sync first before deducting
        $jc_api_url = 'https://odofast.in/api/external/wallet_api.php'; 
        $secret = 'JAPSAN_EXTERNAL_API_SECRET_2026';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $jc_api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'action' => 'deduct',
            'mobile' => $user['mobile'],
            'amount' => $amount_inr,
            'secret' => $secret
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        if (!$data || empty($data['success'])) {
            echo json_encode(['success' => false, 'message' => $data['message'] ?? 'Failed to deduct from Japsan Coin Ecosystem']);
            exit;
        }
        
        // Also update local wallet for fallback
        $db->prepare("UPDATE users SET japsan_wallet = japsan_wallet - ? WHERE id = ?")->execute([$amount_inr, $user['id']]);
    } elseif ($payment_method === 'gold_wallet') {
        $gRate = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1")->fetch();
        if (!$gRate) { echo json_encode(['success' => false, 'message' => 'Gold rate not available']); exit; }
        $required_gold = round($amount_inr / $gRate['rate_per_gram'], 4);
        
        $uStmt = $db->prepare("SELECT total_gold_grams FROM user_summary WHERE user_id = ?");
        $uStmt->execute([$user['id']]);
        $summary = $uStmt->fetch();
        if (!$summary || $summary['total_gold_grams'] < $required_gold) {
            echo json_encode(['success' => false, 'message' => 'Insufficient Gold balance']); exit;
        }
        // Add transaction to deduct gold (sell)
        $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, payment_method, notes) VALUES (?, 'sell', ?, ?, ?, 'gold', 'completed', 'wallet', 'Sold to buy Silver')")
           ->execute([$user['id'], $amount_inr, $required_gold, $gRate['rate_per_gram']]);
    }

    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, payment_method, payment_id) VALUES (?, 'buy', ?, ?, ?, 'silver', 'completed', ?, ?)");
    $stmt->execute([$user['id'], $amount_inr, $silver_grams, $rate['rate_per_gram'], $payment_method, $payment_id]);
    $txn_id = $db->lastInsertId();

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    exit();
}

// Check for referral bonus (if they reach 1000 INR total purchase)
try {
    $uStmt = $db->prepare("SELECT referred_by, referral_bonus_paid FROM users WHERE id = ?");
    $uStmt->execute([$user['id']]);
    $userData = $uStmt->fetch();

    if ($userData && !empty($userData['referred_by']) && $userData['referral_bonus_paid'] == 0) {
        $sumStmt = $db->prepare("SELECT SUM(amount_inr) as total_purchase FROM transactions WHERE user_id = ? AND type = 'buy' AND status = 'completed'");
        $sumStmt->execute([$user['id']]);
        $sumData = $sumStmt->fetch();
        
        if ($sumData && $sumData['total_purchase'] >= 1000) {
            $db->beginTransaction();
            $db->prepare("UPDATE users SET japsan_wallet = japsan_wallet + 500 WHERE id = ?")->execute([$userData['referred_by']]);
            $db->prepare("UPDATE users SET referral_bonus_paid = 1 WHERE id = ?")->execute([$user['id']]);
            $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Referral Bonus', 'You received 500 JC coins for a successful referral purchase', 'general')")->execute([$userData['referred_by']]);
            
            // Get referrer mobile for external sync
            $refStmt = $db->prepare("SELECT mobile FROM users WHERE id = ?");
            $refStmt->execute([$userData['referred_by']]);
            $referrer_mobile = $refStmt->fetchColumn();

            $db->commit();
            
            // Sync with external Japsan Ecosystem
            if ($referrer_mobile) {
                $jc_api_url = 'https://odofast.in/api/external/wallet_api.php'; 
                $secret = 'JAPSAN_EXTERNAL_API_SECRET_2026';
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $jc_api_url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'action' => 'add',
                    'mobile' => $referrer_mobile,
                    'amount' => 500,
                    'description' => 'Referral Bonus from Gold/Silver Purchase',
                    'secret' => $secret
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

// Notification
$db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Silver Purchase Successful', ?, 'transaction')")
   ->execute([$user['id'], "You bought {$silver_grams}g silver for ₹{$amount_inr}"]);

require_once __DIR__ . '/../../api/firebase_helper.php';
$uStmt = $db->prepare("SELECT fcm_token FROM users WHERE id = ?");
$uStmt->execute([$user['id']]);
$fcm_token = $uStmt->fetchColumn();
if ($fcm_token) {
    sendFCMNotification($fcm_token, 'Silver Purchase Successful', "You bought {$silver_grams}g silver for ₹{$amount_inr}");
}

echo json_encode(['success' => true, 'message' => 'Silver purchased successfully', 'data' => [
    'transaction_id' => $txn_id, 'silver_grams' => $silver_grams,
    'amount_inr' => $amount_inr, 'silver_rate' => $rate['rate_per_gram']
]]);
