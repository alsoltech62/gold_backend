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
    $metalType = isset($data->metal_type) ? $data->metal_type : 'gold';

    if ($active && (($frequency === 'daily' && $amount < 10) || ($frequency === 'weekly' && $amount < 50) || ($frequency === 'monthly' && $amount < 100) || ($frequency === 'yearly' && $amount < 1000))) {
        echo json_encode(['success' => false, 'message' => 'Minimum SIP requirement not met']);
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE users SET sip_active = ?, sip_amount = ?, sip_frequency = ? WHERE id = ?");
        $stmt->execute([$active, $amount, $frequency, $user['id']]);

        // Always insert new SIP when setting up (multiple SIPs allowed)
        if ($active) {
            $db->prepare("INSERT INTO user_sips (user_id, amount, frequency, metal_type, status) VALUES (?, ?, ?, ?, 'active')")->execute([$user['id'], $amount, $frequency, $metalType]);
        } else {
            $db->prepare("UPDATE user_sips SET status = 'paused' WHERE user_id = ? AND metal_type = ?")->execute([$user['id'], $metalType]);
        }

        echo json_encode(['success' => true, 'message' => 'SIP settings updated']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update SIP: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
}
