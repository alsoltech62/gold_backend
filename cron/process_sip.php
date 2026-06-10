<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get settings
$stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$penaltyCharge = floatval($settings['sip_penalty_charge'] ?? 50);

// Get users with active SIP
// Mock logic: we assume we run this daily, and check if it's due today
// For demonstration, let's assume we find due SIPs where sip_last_deducted is exactly 30 days ago for monthly
$stmt = $conn->query("
    SELECT * FROM users 
    WHERE sip_active = 1 
    AND (
        (sip_frequency = 'monthly' AND DATEDIFF(CURDATE(), sip_last_deducted) >= 30)
        OR 
        (sip_frequency = 'weekly' AND DATEDIFF(CURDATE(), sip_last_deducted) >= 7)
    )
");

$dueUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($dueUsers as $user) {
    // Try to deduct from inr_wallet as auto debit (mocked auto debit via wallet)
    if ($user['inr_wallet'] >= $user['sip_amount']) {
        // Success deduction
        $conn->beginTransaction();
        try {
            // Deduct wallet
            $conn->query("UPDATE users SET inr_wallet = inr_wallet - {$user['sip_amount']}, sip_last_deducted = NOW() WHERE id = {$user['id']}");
            
            // Get current gold rate
            $rate = $conn->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1")->fetchColumn();
            $grams = $user['sip_amount'] / $rate;
            
            // Add transaction
            $conn->query("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, status, metal_type, transaction_source) 
                          VALUES ({$user['id']}, 'buy', {$user['sip_amount']}, {$grams}, 'completed', 'gold', 'sip')");
            
            // Note: Firebase notification can be added here
            
            $conn->commit();
        } catch(Exception $e) {
            $conn->rollBack();
        }
    } else {
        // Failed due to insufficient funds -> DEDUCT PENALTY
        if ($penaltyCharge > 0 && $user['inr_wallet'] >= $penaltyCharge) {
            // Deduct penalty charge
            $conn->query("UPDATE users SET inr_wallet = inr_wallet - {$penaltyCharge} WHERE id = {$user['id']}");
            // Optional: Insert transaction record for penalty
            $conn->query("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, status, transaction_source) 
                          VALUES ({$user['id']}, 'sip_penalty', {$penaltyCharge}, 0, 'completed', 'wallet')");
        }
        
        // Deactivate SIP to prevent infinite penalty loop? Or leave active and penalty every day? 
        // Let's pause SIP
        $conn->query("UPDATE users SET sip_active = 0 WHERE id = {$user['id']}");
    }
}

echo "SIP Processed successfully.";
