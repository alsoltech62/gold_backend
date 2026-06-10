<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

authenticateAdmin();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Total SIP amount globally
    $totalStmt = $db->query("SELECT COALESCE(SUM(amount_inr), 0) as total_sip_invested, COALESCE(SUM(gold_grams), 0) as total_sip_gold FROM transactions WHERE transaction_source = 'sip' AND status = 'completed'");
    $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

    // Recent SIP transactions
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $countStmt = $db->query("SELECT COUNT(*) FROM transactions WHERE transaction_source = 'sip'");
    $count = $countStmt->fetchColumn();

    $stmt = $db->prepare("SELECT t.*, u.name as user_name, u.mobile FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.transaction_source = 'sip' ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_invested' => (float)$totals['total_sip_invested'],
            'total_gold' => (float)$totals['total_sip_gold'],
            'history' => $history
        ],
        'pagination' => ['total' => (int)$count, 'page' => $page, 'pages' => ceil($count / $limit)]
    ]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
