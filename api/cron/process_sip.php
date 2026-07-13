<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// This file should ideally be protected or run from CLI only
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'secret_cron_key')) {
    http_response_code(403);
    exit('Forbidden');
}

$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    $gRateStmt = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1");
    $gRate = $gRateStmt->fetch();

    $sRateStmt = $db->query("SELECT rate_per_gram FROM silver_rates ORDER BY rate_date DESC LIMIT 1");
    $sRate = $sRateStmt->fetch();

    if (!$gRate || !$sRate) {
        throw new Exception("Rates not available");
    }

    $gold_rate = $gRate['rate_per_gram'];
    $silver_rate = $sRate['rate_per_gram'];

    // Fetch Penalty Charge Setting
    $penaltyStmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'sip_penalty_charge'");
    $penaltyRow = $penaltyStmt->fetch();
    $penalty_charge = $penaltyRow ? (float)$penaltyRow['setting_value'] : 50.0;

    $frequencies = [
        'daily' => 'DATE(last_deducted) < CURDATE()',
        'monthly' => 'DATE_ADD(DATE(last_deducted), INTERVAL 1 MONTH) <= CURDATE()',
        'weekly' => 'DATE_ADD(DATE(last_deducted), INTERVAL 1 WEEK) <= CURDATE()',
        'yearly' => 'DATE_ADD(DATE(last_deducted), INTERVAL 1 YEAR) <= CURDATE()'
    ];

    $processed_count = 0;
    $penalties_count = 0;

    foreach ($frequencies as $freq => $condition) {
        $stmt = $db->prepare("SELECT us.id as sip_id, us.amount, us.metal_type, u.id as user_id, u.inr_wallet FROM user_sips us JOIN users u ON us.user_id = u.id WHERE us.status = 'active' AND us.frequency = ? AND (us.last_deducted IS NULL OR $condition)");
        $stmt->execute([$freq]);
        $sips = $stmt->fetchAll();

        foreach ($sips as $sip) {
            $amount = $sip['amount'];
            $metal_type = $sip['metal_type'] ?? 'gold';
            $rate_per_gram = $metal_type === 'silver' ? $silver_rate : $gold_rate;

            if ($sip['inr_wallet'] >= $amount) {
                // Successful SIP
                $grams = round($amount / $rate_per_gram, 4);
                $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ? WHERE id = ?")->execute([$amount, $sip['user_id']]);
                $db->prepare("UPDATE user_sips SET last_deducted = NOW() WHERE id = ?")->execute([$sip['sip_id']]);
                $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'buy', ?, ?, ?, ?, 'completed', 'sip', ?)")
                   ->execute([$sip['user_id'], $amount, $grams, $rate_per_gram, $metal_type, ucfirst($freq) . ' ' . ucfirst($metal_type) . ' SIP Deduction']);
                $processed_count++;
            } else {
                // Missed SIP (Penalty)
                $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ? WHERE id = ?")->execute([$penalty_charge, $sip['user_id']]);
                $db->prepare("UPDATE user_sips SET last_deducted = NOW() WHERE id = ?")->execute([$sip['sip_id']]);
                $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'sip_penalty', ?, 0, ?, 'fiat', 'completed', 'sip', ?)")
                   ->execute([$sip['user_id'], $penalty_charge, $rate_per_gram, 'Penalty for missed ' . $freq . ' ' . ucfirst($metal_type) . ' SIP']);
                $penalties_count++;
            }
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'SIP processing complete', 'processed' => $processed_count, 'penalties_applied' => $penalties_count]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'SIP processing failed: ' . $e->getMessage()]);
}
