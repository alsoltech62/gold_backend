<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $amount = (float)($data->amount ?? 0);
    $frequency = $data->frequency ?? 'monthly';
    $active = isset($data->active) ? (int)$data->active : 1;

    if ($active && (($frequency === 'daily' && $amount < 10) || ($frequency === 'monthly' && $amount < 100))) {
        echo json_encode(['success' => false, 'message' => 'Minimum SIP is 10 Rs/day or 100 Rs/month']);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE users SET sip_active = ?, sip_amount = ?, sip_frequency = ? WHERE id = ?");
        $stmt->execute([$active, $amount, $frequency, $user['id']]);

        echo json_encode(['success' => true, 'message' => 'SIP settings updated']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update SIP: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
}
