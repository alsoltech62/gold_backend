<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db   = (new Database())->getConnection();

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$type  = $_GET['type'] ?? '';

$where = "WHERE t.user_id=?";
$params = [$user['id']];
if ($type) { $where .= " AND t.type=?"; $params[] = $type; }

$total = $db->prepare("SELECT COUNT(*) FROM transactions t $where");
$total->execute($params);
$count = $total->fetchColumn();

$stmt = $db->prepare("SELECT t.*, u.name as created_by_name FROM transactions t LEFT JOIN users u ON t.created_by=u.id $where ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);

echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'pagination' => [
    'total' => (int)$count, 'page' => $page, 'limit' => $limit,
    'pages' => ceil($count / $limit)
]]);
