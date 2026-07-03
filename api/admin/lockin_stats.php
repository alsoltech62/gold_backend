<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

authenticateAdmin();

$db = new Database();
$conn = $db->getConnection();

$totalLockedGold = $conn->query("SELECT COALESCE(SUM(gold_grams),0) FROM user_lock_ins WHERE status='active' AND metal_type='gold'")->fetchColumn();
$totalLockedSilver = $conn->query("SELECT COALESCE(SUM(silver_grams),0) FROM user_lock_ins WHERE status='active' AND metal_type='silver'")->fetchColumn();
$activePlans = $conn->query("SELECT COUNT(*) FROM user_lock_ins WHERE status='active'")->fetchColumn();
$pendingReturns = $conn->query("
    SELECT COALESCE(SUM(l.gold_grams * (p.return_percentage / 100)),0) 
    FROM user_lock_ins l 
    JOIN lock_in_plans p ON l.plan_id = p.id 
    WHERE l.status='active'
")->fetchColumn();
$earlyUnlocks = $conn->query("SELECT COUNT(*) FROM user_lock_ins WHERE status='early_unlock'")->fetchColumn();

// Get active lock-ins
$activeLocks = $conn->query("
    SELECT l.*, u.name as user_name, u.mobile, p.months, p.return_percentage 
    FROM user_lock_ins l
    JOIN users u ON l.user_id = u.id
    JOIN lock_in_plans p ON l.plan_id = p.id
    WHERE l.status='active'
    ORDER BY l.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get plans
$plans = $conn->query("SELECT * FROM lock_in_plans WHERE status='active' ORDER BY months ASC")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => [
    'total_locked_gold' => round((float)$totalLockedGold, 4),
    'total_locked_silver' => round((float)$totalLockedSilver, 4),
    'active_plans' => (int)$activePlans,
    'pending_returns' => round((float)$pendingReturns, 4),
    'early_unlocks' => (int)$earlyUnlocks,
    'active_locks' => $activeLocks,
    'plans' => $plans
]]);
