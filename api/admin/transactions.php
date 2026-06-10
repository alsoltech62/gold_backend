<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$admin = authenticateAdmin();
$db    = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? '';
    $type   = $_GET['type']   ?? '';
    $where  = "WHERE 1=1";
    $params = [];
    if ($status) { $where .= " AND t.status=?"; $params[] = $status; }
    if ($type)   { $where .= " AND t.type=?";   $params[] = $type; }
    $total = $db->prepare("SELECT COUNT(*) FROM transactions t $where");
    $total->execute($params);
    $count = $total->fetchColumn();
    $stmt  = $db->prepare("SELECT t.*, u.name as user_name, u.mobile FROM transactions t JOIN users u ON t.user_id=u.id $where ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'pagination' => ['total' => (int)$count, 'page' => $page, 'pages' => ceil($count / $limit)]]);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $type = $data['type'] ?? '';
    if (!in_array($type, ['buy', 'sell', 'delivery'])) { echo json_encode(['success' => false, 'message' => 'Invalid type']); exit(); }
    $rate = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1")->fetchColumn();
    $gold_grams = ($data['gold_grams'] ?? null) ?: round($data['amount_inr'] / $rate, 4);
    $amount_inr = ($data['amount_inr'] ?? null) ?: round($gold_grams * $rate, 2);
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount_inr, gold_grams, gold_rate, status, payment_method, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$data['user_id'], $type, $amount_inr, $gold_grams, $rate, $data['status'] ?? 'completed', $data['payment_method'] ?? 'manual', $data['notes'] ?? '', $admin['id']]);
    echo json_encode(['success' => true, 'message' => 'Transaction added', 'id' => $db->lastInsertId()]);

} elseif ($method === 'PUT') {
    $id   = (int)($_GET['id'] ?? 0);
    $data = json_decode(file_get_contents('php://input'), true);
    $db->prepare("UPDATE transactions SET status=?, notes=? WHERE id=?")->execute([$data['status'], $data['notes'] ?? '', $id]);
    if ($data['status'] === 'completed') {
        $txn = $db->prepare("SELECT user_id, type, gold_grams FROM transactions WHERE id=?");
        $txn->execute([$id]);
        $t = $txn->fetch();
        $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'transaction')")
           ->execute([$t['user_id'], 'Transaction Updated', "Your {$t['type']} request for {$t['gold_grams']}g has been {$data['status']}"]);
    }
    echo json_encode(['success' => true, 'message' => 'Transaction updated']);
}
