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

    if (!$gRate) {
        throw new Exception("Gold rate not available");
    }

    $rate_per_gram = $gRate['rate_per_gram'];

    // Fetch Penalty Charge Setting
    $penaltyStmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'sip_penalty_charge'");
    $penaltyRow = $penaltyStmt->fetch();
    $penalty_charge = $penaltyRow ? (float)$penaltyRow['setting_value'] : 50.0;

    // Process daily SIP (Successful)
    $stmt = $db->prepare("SELECT id, inr_wallet, sip_amount FROM users WHERE sip_active = 1 AND sip_frequency = 'daily' AND inr_wallet >= sip_amount AND (sip_last_deducted IS NULL OR DATE(sip_last_deducted) < CURDATE())");
    $stmt->execute();
    $dailyUsers = $stmt->fetchAll();

    foreach ($dailyUsers as $user) {
        $amount = $user['sip_amount'];
        $gold_grams = round($amount / $rate_per_gram, 4);

        $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ?, sip_last_deducted = NOW() WHERE id = ?")->execute([$amount, $user['id']]);
        
        $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'buy', ?, ?, ?, 'gold', 'completed', 'sip', 'Daily SIP Deduction')")
           ->execute([$user['id'], $amount, $gold_grams, $rate_per_gram]);
    }

    // Process monthly SIP
    $stmt = $db->prepare("SELECT id, inr_wallet, sip_amount FROM users WHERE sip_active = 1 AND sip_frequency = 'monthly' AND inr_wallet >= sip_amount AND (sip_last_deducted IS NULL OR DATE_ADD(DATE(sip_last_deducted), INTERVAL 1 MONTH) <= CURDATE())");
    $stmt->execute();
    $monthlyUsers = $stmt->fetchAll();

    foreach ($monthlyUsers as $user) {
        $amount = $user['sip_amount'];
        $gold_grams = round($amount / $rate_per_gram, 4);

        $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ?, sip_last_deducted = NOW() WHERE id = ?")->execute([$amount, $user['id']]);
        
        $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'buy', ?, ?, ?, 'gold', 'completed', 'sip', 'Monthly SIP Deduction')")
           ->execute([$user['id'], $amount, $gold_grams, $rate_per_gram]);
    }

    // Process weekly SIP
    $stmt = $db->prepare("SELECT id, inr_wallet, sip_amount FROM users WHERE sip_active = 1 AND sip_frequency = 'weekly' AND inr_wallet >= sip_amount AND (sip_last_deducted IS NULL OR DATE_ADD(DATE(sip_last_deducted), INTERVAL 1 WEEK) <= CURDATE())");
    $stmt->execute();
    $weeklyUsers = $stmt->fetchAll();

    foreach ($weeklyUsers as $user) {
        $amount = $user['sip_amount'];
        $gold_grams = round($amount / $rate_per_gram, 4);
        $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ?, sip_last_deducted = NOW() WHERE id = ?")->execute([$amount, $user['id']]);
        $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'buy', ?, ?, ?, 'gold', 'completed', 'sip', 'Weekly SIP Deduction')")
           ->execute([$user['id'], $amount, $gold_grams, $rate_per_gram]);
    }

    // Process yearly SIP
    $stmt = $db->prepare("SELECT id, inr_wallet, sip_amount FROM users WHERE sip_active = 1 AND sip_frequency = 'yearly' AND inr_wallet >= sip_amount AND (sip_last_deducted IS NULL OR DATE_ADD(DATE(sip_last_deducted), INTERVAL 1 YEAR) <= CURDATE())");
    $stmt->execute();
    $yearlyUsers = $stmt->fetchAll();

    foreach ($yearlyUsers as $user) {
        $amount = $user['sip_amount'];
        $gold_grams = round($amount / $rate_per_gram, 4);
        $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ?, sip_last_deducted = NOW() WHERE id = ?")->execute([$amount, $user['id']]);
        $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'buy', ?, ?, ?, 'gold', 'completed', 'sip', 'Yearly SIP Deduction')")
           ->execute([$user['id'], $amount, $gold_grams, $rate_per_gram]);
    }

    // Process MISSED SIPs (Insufficient Funds) - Daily
    $stmt = $db->prepare("SELECT id, inr_wallet, sip_amount FROM users WHERE sip_active = 1 AND sip_frequency = 'daily' AND inr_wallet < sip_amount AND (sip_last_deducted IS NULL OR DATE(sip_last_deducted) < CURDATE())");
    $stmt->execute();
    $missedDaily = $stmt->fetchAll();

    foreach ($missedDaily as $user) {
        $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ?, sip_last_deducted = NOW() WHERE id = ?")->execute([$penalty_charge, $user['id']]);
        $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'sip_penalty', ?, 0, ?, 'fiat', 'completed', 'sip', 'Penalty for missed daily SIP')")
           ->execute([$user['id'], $penalty_charge, $rate_per_gram]);
    }

    // Process MISSED SIPs (Insufficient Funds) - Monthly
    $stmt = $db->prepare("SELECT id, inr_wallet, sip_amount FROM users WHERE sip_active = 1 AND sip_frequency = 'monthly' AND inr_wallet < sip_amount AND (sip_last_deducted IS NULL OR DATE_ADD(DATE(sip_last_deducted), INTERVAL 1 MONTH) <= CURDATE())");
    $stmt->execute();
    $missedMonthly = $stmt->fetchAll();

    foreach ($missedMonthly as $user) {
        $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ?, sip_last_deducted = NOW() WHERE id = ?")->execute([$penalty_charge, $user['id']]);
        $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'sip_penalty', ?, 0, ?, 'fiat', 'completed', 'sip', 'Penalty for missed monthly SIP')")
           ->execute([$user['id'], $penalty_charge, $rate_per_gram]);
    }

    // Process MISSED SIPs (Insufficient Funds) - Weekly
    $stmt = $db->prepare("SELECT id, inr_wallet, sip_amount FROM users WHERE sip_active = 1 AND sip_frequency = 'weekly' AND inr_wallet < sip_amount AND (sip_last_deducted IS NULL OR DATE_ADD(DATE(sip_last_deducted), INTERVAL 1 WEEK) <= CURDATE())");
    $stmt->execute();
    $missedWeekly = $stmt->fetchAll();
    foreach ($missedWeekly as $user) {
        $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ?, sip_last_deducted = NOW() WHERE id = ?")->execute([$penalty_charge, $user['id']]);
        $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'sip_penalty', ?, 0, ?, 'fiat', 'completed', 'sip', 'Penalty for missed weekly SIP')")->execute([$user['id'], $penalty_charge, $rate_per_gram]);
    }

    // Process MISSED SIPs (Insufficient Funds) - Yearly
    $stmt = $db->prepare("SELECT id, inr_wallet, sip_amount FROM users WHERE sip_active = 1 AND sip_frequency = 'yearly' AND inr_wallet < sip_amount AND (sip_last_deducted IS NULL OR DATE_ADD(DATE(sip_last_deducted), INTERVAL 1 YEAR) <= CURDATE())");
    $stmt->execute();
    $missedYearly = $stmt->fetchAll();
    foreach ($missedYearly as $user) {
        $db->prepare("UPDATE users SET inr_wallet = inr_wallet - ?, sip_last_deducted = NOW() WHERE id = ?")->execute([$penalty_charge, $user['id']]);
        $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, metal_type, status, transaction_source, notes) VALUES (?, 'sip_penalty', ?, 0, ?, 'fiat', 'completed', 'sip', 'Penalty for missed yearly SIP')")->execute([$user['id'], $penalty_charge, $rate_per_gram]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'SIP processing complete', 'daily_processed' => count($dailyUsers), 'monthly_processed' => count($monthlyUsers), 'penalties_applied' => count($missedDaily) + count($missedMonthly)]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'SIP processing failed: ' . $e->getMessage()]);
}
