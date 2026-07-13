<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $metal_type = isset($_GET['metal_type']) ? $_GET['metal_type'] : null;

    $totalQuery = "SELECT COALESCE(SUM(amount_inr), 0) as total_sip_invested, COALESCE(SUM(gold_grams), 0) as total_sip_gold FROM transactions WHERE user_id = ? AND transaction_source = 'sip' AND status = 'completed'";
    $totalParams = [$user['id']];
    if ($metal_type) {
        $totalQuery .= " AND metal_type = ?";
        $totalParams[] = $metal_type;
    }
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute($totalParams);
    $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

    // Recent SIP transactions
    $historyQuery = "SELECT * FROM transactions WHERE user_id = ? AND transaction_source = 'sip'";
    $historyParams = [$user['id']];
    if ($metal_type) {
        $historyQuery .= " AND metal_type = ?";
        $historyParams[] = $metal_type;
    }
    $historyQuery .= " ORDER BY created_at DESC LIMIT 50";
    
    $stmt = $db->prepare($historyQuery);
    $stmt->execute($historyParams);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Active SIP plans from user_sips table
    $activeSipQuery = "SELECT id, amount, frequency, metal_type, status, created_at FROM user_sips WHERE user_id = ? AND status = 'active'";
    $activeSipParams = [$user['id']];
    if ($metal_type) {
        $activeSipQuery .= " AND metal_type = ?";
        $activeSipParams[] = $metal_type;
    }
    $activeSipQuery .= " ORDER BY created_at DESC";
    $activeSipStmt = $db->prepare($activeSipQuery);
    $activeSipStmt->execute($activeSipParams);
    $active_sips = $activeSipStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback: if user_sips is empty, read from users table
    if (empty($active_sips) && (!$metal_type || $metal_type === 'gold')) {
        // Check if user has ANY rows in user_sips
        $checkAny = $db->prepare("SELECT COUNT(*) FROM user_sips WHERE user_id = ?");
        $checkAny->execute([$user['id']]);
        if ($checkAny->fetchColumn() == 0) {
            $userSipStmt = $db->prepare("SELECT sip_active, sip_amount, sip_frequency FROM users WHERE id = ?");
            $userSipStmt->execute([$user['id']]);
            $userSip = $userSipStmt->fetch(PDO::FETCH_ASSOC);
            $u_amount = (float)($userSip['sip_amount'] ?? 0);
            $u_active = (int)($userSip['sip_active'] ?? 0);
            if ($u_active == 1 || $u_amount > 0) {
                $active_sips = [[
                    'id'         => null,
                    'amount'     => $u_amount,
                    'frequency'  => $userSip['sip_frequency'] ?? 'monthly',
                    'metal_type' => 'gold',
                    'status'     => 'active',
                    'created_at' => null,
                ]];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'total_invested' => (float)$totals['total_sip_invested'],
            'total_gold'     => (float)$totals['total_sip_gold'],
            'active_sips'    => $active_sips,
            'history'        => $history
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
