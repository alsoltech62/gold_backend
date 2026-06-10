<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user = authenticate();

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? AND type = 'delivery' ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $deliveries]);
