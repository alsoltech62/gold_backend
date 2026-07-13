<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $sipId = isset($data->sip_id) ? (int)$data->sip_id : 0;

    if (!$sipId) {
        echo json_encode(['success' => false, 'message' => 'SIP ID is required']);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE user_sips SET status = 'cancelled' WHERE id = ? AND user_id = ?");
        $stmt->execute([$sipId, $user['id']]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'SIP cancelled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'SIP not found or already cancelled']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel SIP: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
}
