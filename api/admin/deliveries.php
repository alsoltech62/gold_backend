<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
if (!$user['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("
        SELECT d.*, u.name, u.mobile, u.email 
        FROM delivery_requests d 
        JOIN users u ON d.user_id = u.id 
        ORDER BY d.created_at DESC
    ");
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $delivery_id = $data->delivery_id ?? null;
    $status = $data->status ?? 'pending';
    $tracking_number = $data->tracking_number ?? null;

    if (!$delivery_id) {
        echo json_encode(['success' => false, 'message' => 'Delivery ID required']);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE delivery_requests SET status = ?, tracking_number = ? WHERE id = ?");
        $stmt->execute([$status, $tracking_number, $delivery_id]);
        echo json_encode(['success' => true, 'message' => 'Delivery updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update delivery: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
}
