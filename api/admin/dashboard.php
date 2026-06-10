<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

authenticateAdmin();
$db = (new Database())->getConnection();

$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE is_admin=0")->fetchColumn();
$totalGold     = $db->query("SELECT COALESCE(SUM(total_gold_grams),0) FROM user_summary")->fetchColumn();
$totalSilver   = $db->query("SELECT COALESCE(SUM(total_silver_grams),0) FROM user_summary")->fetchColumn();
$totalInvested = $db->query("SELECT COALESCE(SUM(total_invested_inr),0) FROM user_summary")->fetchColumn();
$totalInrWallet = $db->query("SELECT COALESCE(SUM(inr_wallet),0) FROM users WHERE is_admin=0")->fetchColumn();
$totalJapsanWallet = $db->query("SELECT COALESCE(SUM(japsan_wallet),0) FROM users WHERE is_admin=0")->fetchColumn();
$todayTxns     = $db->query("SELECT COUNT(*) FROM transactions WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$pendingTxns   = $db->query("SELECT COUNT(*) FROM transactions WHERE status='pending'")->fetchColumn();
$pendingTickets= $db->query("SELECT COUNT(*) FROM support_tickets WHERE status='open' OR status='pending'")->fetchColumn();
$pendingDelivery=$db->query("SELECT COUNT(*) FROM delivery_requests WHERE status='pending' OR status='processing'")->fetchColumn();
$todayBought   = $db->query("SELECT COALESCE(SUM(gold_grams),0) FROM transactions WHERE type='buy' AND status='completed' AND metal_type='gold' AND DATE(created_at)=CURDATE()")->fetchColumn();
$totalBought   = $db->query("SELECT COALESCE(SUM(gold_grams),0) FROM transactions WHERE type='buy' AND status='completed' AND metal_type='gold'")->fetchColumn();
$totalSold     = $db->query("SELECT COALESCE(SUM(gold_grams),0) FROM transactions WHERE type IN('sell','delivery') AND status='completed' AND metal_type='gold'")->fetchColumn();
$currentRate   = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1")->fetchColumn();

// New Lock-in and SIP Stats
$totalLockedGold = $db->query("SELECT COALESCE(SUM(gold_grams),0) FROM user_lock_ins WHERE status='active'")->fetchColumn();
$activeLockIns   = $db->query("SELECT COUNT(*) FROM user_lock_ins WHERE status='active'")->fetchColumn();
$activeSips      = $db->query("SELECT COUNT(*) FROM users WHERE sip_active=1")->fetchColumn();
$monthlySipVol   = $db->query("SELECT COALESCE(SUM(sip_amount),0) FROM users WHERE sip_active=1 AND sip_frequency='monthly'")->fetchColumn();

$mismatch = abs((float)$totalBought - (float)$totalSold - (float)$totalGold) > 0.001;

// Revenue Statistics
$buyRevenue = $db->query("SELECT COALESCE(SUM(amount_inr),0) FROM transactions WHERE type='buy' AND status='completed'")->fetchColumn();
$sellRevenue = $db->query("SELECT COALESCE(SUM(amount_inr),0) FROM transactions WHERE type='sell' AND status='completed'")->fetchColumn();
$deliveryRevenue = $db->query("SELECT COALESCE(SUM(total_cost),0) FROM delivery_requests WHERE status='delivered'")->fetchColumn();
$totalRevenue = (float)$buyRevenue + (float)$deliveryRevenue;

echo json_encode(['success' => true, 'data' => [
    'total_users'         => (int)$totalUsers,
    'total_gold_held_grams'=> round((float)$totalGold, 4),
    'total_silver_held_grams'=> round((float)$totalSilver, 4),
    'total_invested_inr'  => round((float)$totalInvested, 2),
    'total_inr_wallet'    => round((float)$totalInrWallet, 2),
    'total_japsan_wallet' => round((float)$totalJapsanWallet, 2),
    'total_bought_grams'  => round((float)$totalBought, 4),
    'total_sold_grams'    => round((float)$totalSold, 4),
    'today_transactions'  => (int)$todayTxns,
    'pending_transactions'=> (int)$pendingTxns,
    'pending_tickets'     => (int)$pendingTickets,
    'pending_deliveries'  => (int)$pendingDelivery,
    'today_bought_grams'  => round((float)$todayBought, 4),
    'current_gold_rate'   => (float)$currentRate,
    'gold_mismatch_alert' => $mismatch,
    'total_locked_gold'   => round((float)$totalLockedGold, 4),
    'active_lock_ins'     => (int)$activeLockIns,
    'active_sips'         => (int)$activeSips,
    'monthly_sip_volume'  => round((float)$monthlySipVol, 2),
    'buy_revenue'         => round((float)$buyRevenue, 2),
    'sell_revenue'        => round((float)$sellRevenue, 2),
    'delivery_revenue'    => round((float)$deliveryRevenue, 2),
    'total_revenue'       => round((float)$totalRevenue, 2)
]]);
