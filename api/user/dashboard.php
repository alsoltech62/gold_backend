<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

// Sync Japsan Coin Wallet from external ecosystem
try {
    $jc_api_url = 'https://odofast.in/api/external/wallet_api.php'; 
    $secret = 'JAPSAN_EXTERNAL_API_SECRET_2026';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $jc_api_url . '?action=get_balance&mobile=' . urlencode($user['mobile']) . '&secret=' . $secret);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['success']) && $data['success'] && isset($data['balance'])) {
            $db->prepare("UPDATE users SET japsan_wallet = ? WHERE id = ?")->execute([$data['balance'], $user['id']]);
        }
    }
} catch (Exception $e) {}

$summary = $db->prepare("
    SELECT u.inr_wallet, u.silver_wallet, u.japsan_wallet, u.sip_active, u.sip_amount, u.sip_frequency, 
           us.total_gold_grams, us.total_silver_grams, us.total_invested_inr 
    FROM users u 
    LEFT JOIN user_summary us ON u.id = us.user_id 
    WHERE u.id=?");
$summary->execute([$user['id']]);
$userData = $summary->fetch();

$rate = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1")->fetch();
$silver_rate = $db->query("SELECT rate_per_gram FROM silver_rates ORDER BY rate_date DESC LIMIT 1")->fetch();

$total_gold = (float)($userData['total_gold_grams'] ?? 0);
$total_silver = (float)($userData['total_silver_grams'] ?? 0);
$total_invested = (float)($userData['total_invested_inr'] ?? 0);
$gold_current_value = round($total_gold * ($rate['rate_per_gram'] ?? 0), 2);
$silver_current_value = round($total_silver * ($silver_rate['rate_per_gram'] ?? 0), 2);
$current_value = $gold_current_value + $silver_current_value;
$profit_loss = round($current_value - $total_invested, 2);

$txns = $db->prepare("SELECT id, type, amount_inr, gold_grams, gold_rate, status, created_at FROM transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$txns->execute([$user['id']]);

$notifs = $db->prepare("SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$notifs->execute([$user['id']]);

echo json_encode(['success' => true, 'data' => [
    'inr_wallet'        => (float)($userData['inr_wallet'] ?? 0),
    'silver_wallet'     => (float)($userData['silver_wallet'] ?? 0),
    'japsan_wallet'     => (float)($userData['japsan_wallet'] ?? 0),
    'sip_active'        => (bool)($userData['sip_active'] ?? false),
    'sip_amount'        => (float)($userData['sip_amount'] ?? 0),
    'sip_frequency'     => $userData['sip_frequency'] ?? 'monthly',
    'total_gold_grams'  => $total_gold,
    'total_silver_grams'=> $total_silver,
    'total_invested_inr'=> $total_invested,
    'current_value_inr' => $current_value,
    'profit_loss_inr'   => $profit_loss,
    'gold_rate'         => (float)($rate['rate_per_gram'] ?? 0),
    'silver_rate'       => (float)($silver_rate['rate_per_gram'] ?? 0),
    'recent_transactions' => $txns->fetchAll(),
    'notifications'     => $notifs->fetchAll()
]]);
