<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$admin = authenticateAdmin();
$db    = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rates = $db->query("SELECT * FROM gold_rates ORDER BY rate_date DESC LIMIT 30")->fetchAll();
    echo json_encode(['success' => true, 'data' => $rates]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $rate = (float)($data['rate_per_gram'] ?? 0);
    $date = $data['date'] ?? date('Y-m-d');
    if ($rate <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid rate']); exit(); }
    $stmt = $db->prepare("INSERT INTO gold_rates (rate_per_gram, rate_date, updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE rate_per_gram=?, updated_by=?");
    $stmt->execute([$rate, $date, $admin['id'], $rate, $admin['id']]);
    $db->query("INSERT INTO notifications (user_id, title, message, type) SELECT id, 'Gold Rate Updated', CONCAT('Today\\'s gold rate: ₹{$rate}/gram'), 'gold_rate' FROM users WHERE is_admin=0");
    echo json_encode(['success' => true, 'message' => 'Gold rate updated']);
}
