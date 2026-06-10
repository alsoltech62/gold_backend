<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

authenticateAdmin();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->query("SELECT * FROM sip_plans ORDER BY min_amount ASC");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $plans]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $plan_id = $data['id'] ?? null;
    $plan_name = $data['plan_name'] ?? 'New SIP Plan';
    $min_amount = $data['min_amount'] ?? 500;
    $max_amount = $data['max_amount'] ?? 999999;
    $frequency = $data['frequency'] ?? 'monthly';
    $status = $data['status'] ?? 'active';

    if ($plan_id) {
        $stmt = $db->prepare("UPDATE sip_plans SET plan_name = ?, min_amount = ?, max_amount = ?, frequency = ?, status = ? WHERE id = ?");
        $stmt->execute([$plan_name, $min_amount, $max_amount, $frequency, $status, $plan_id]);
        echo json_encode(['success' => true, 'message' => 'SIP Plan updated successfully']);
    } else {
        $stmt = $db->prepare("INSERT INTO sip_plans (plan_name, min_amount, max_amount, frequency, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$plan_name, $min_amount, $max_amount, $frequency, $status]);
        echo json_encode(['success' => true, 'message' => 'SIP Plan created successfully']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
