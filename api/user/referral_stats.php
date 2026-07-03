<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get total referrals
    $stmt = $db->prepare("SELECT COUNT(*) as total_referrals FROM users WHERE referred_by = ?");
    $stmt->execute([$user['id']]);
    $total_referrals = $stmt->fetch()['total_referrals'];

    // Get total rewards from transactions (source = referral)
    $stmt = $db->prepare("SELECT SUM(gold_grams) as total_silver_bonus FROM transactions WHERE user_id = ? AND transaction_source = 'referral' AND metal_type = 'silver'");
    $stmt->execute([$user['id']]);
    $total_silver_bonus = $stmt->fetch()['total_silver_bonus'] ?? 0;

    // Get referred users list
    $stmt = $db->prepare("SELECT name, mobile, created_at FROM users WHERE referred_by = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $referred_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_referrals' => $total_referrals,
            'total_silver_bonus' => $total_silver_bonus,
            'referred_users' => $referred_users
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
