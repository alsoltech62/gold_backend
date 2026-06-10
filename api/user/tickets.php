<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT id, subject, description, status, created_at FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $subject = $data->subject ?? '';
    $description = $data->description ?? '';

    if (empty($subject) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Subject and description are required']);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO support_tickets (user_id, subject, description) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $subject, $description]);
        echo json_encode(['success' => true, 'message' => 'Ticket raised successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to raise ticket: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
}
