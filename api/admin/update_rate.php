<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Authenticate Admin
$admin = authenticateAdmin();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $rate = (float)($data['rate_per_gram'] ?? 0);
    $date = $data['date'] ?? date('Y-m-d');

    if ($rate <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid rate']);
        exit();
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO gold_rates (rate_per_gram, rate_date, updated_by) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE rate_per_gram = ?, updated_by = ?
        ");
        
        $stmt->execute([$rate, $date, $admin['id'], $rate, $admin['id']]);

        // Notify users
        $notificationMsg = "Today's gold rate: ₹" . number_format($rate, 2) . "/gram";
        $notifStmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            SELECT id, 'Gold Rate Updated', ?, 'gold_rate' 
            FROM users WHERE is_admin = 0
        ");
        $notifStmt->execute([$notificationMsg]);

        echo json_encode(['success' => true, 'message' => 'Gold rate updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
