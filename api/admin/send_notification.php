<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../firebase_helper.php';

header('Content-Type: application/json');

$admin = authenticate(true); // Assuming true requires admin

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$title = $data['title'] ?? '';
$message = $data['message'] ?? '';
$user_id = $data['user_id'] ?? null; // Null means broadcast to all

if (empty($title) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title and message are required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Insert into notifications table
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'general')");
    $stmt->execute([$user_id, $title, $message]);

    // Fetch tokens to send FCM
    if ($user_id) {
        $uStmt = $conn->prepare("SELECT fcm_token FROM users WHERE id = ?");
        $uStmt->execute([$user_id]);
        $tokens = $uStmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $uStmt = $conn->query("SELECT fcm_token FROM users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
        $tokens = $uStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $successCount = 0;
    foreach ($tokens as $token) {
        if ($token) {
            sendFCMNotification($token, $title, $message);
            $successCount++;
        }
    }

    echo json_encode(['success' => true, 'message' => "Notification sent successfully to $successCount devices"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
