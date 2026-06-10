<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Total SIP amount
    $totalStmt = $db->prepare("SELECT COALESCE(SUM(amount_inr), 0) as total_sip_invested, COALESCE(SUM(gold_grams), 0) as total_sip_gold FROM transactions WHERE user_id = ? AND transaction_source = 'sip' AND status = 'completed'");
    $totalStmt->execute([$user['id']]);
    $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

    // Recent SIP transactions
    $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? AND transaction_source = 'sip' ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user['id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_invested' => (float)$totals['total_sip_invested'],
            'total_gold' => (float)$totals['total_sip_gold'],
            'history' => $history
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
