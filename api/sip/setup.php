<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user = authenticate();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['amount']) || !isset($data['frequency'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$amount = floatval($data['amount']);
$frequency = in_array($data['frequency'], ['daily', 'weekly', 'monthly', 'yearly']) ? $data['frequency'] : 'monthly';

if ($amount < 100) {
    echo json_encode(['success' => false, 'message' => 'Minimum SIP amount is ₹100']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$conn->exec("CREATE TABLE IF NOT EXISTS user_sips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL,
    metal_type ENUM('gold', 'silver') DEFAULT 'gold',
    status ENUM('active', 'paused', 'cancelled') DEFAULT 'active',
    last_deducted DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$stmt = $conn->prepare("SELECT id FROM user_sips WHERE user_id = ? AND frequency = ? AND status = 'active'");
$stmt->execute([$user['id'], $frequency]);
$existing = $stmt->fetch();

if ($existing) {
    $updateStmt = $conn->prepare("UPDATE user_sips SET amount = ? WHERE id = ?");
    $result = $updateStmt->execute([$amount, $existing['id']]);
} else {
    $insertStmt = $conn->prepare("INSERT INTO user_sips (user_id, amount, frequency, status) VALUES (?, ?, ?, 'active')");
    $result = $insertStmt->execute([$user['id'], $amount, $frequency]);
}

if ($result) {
    echo json_encode(['success' => true, 'message' => 'SIP Setup successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to setup SIP']);
}
