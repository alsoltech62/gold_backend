<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

authenticateAdmin();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->query("SELECT * FROM lock_in_plans ORDER BY months ASC");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $plans]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Add/Edit Plan
    $plan_id = $data['id'] ?? null;
    $plan_name = $data['plan_name'] ?? 'New Plan';
    $months = $data['months'] ?? 6;
    $return_percentage = $data['return_percentage'] ?? 5.0;
    $min_invest = $data['min_investment'] ?? 1.0;
    $max_invest = $data['max_investment'] ?? 999.0;
    $penalty = $data['penalty_percentage'] ?? 0.0;
    $status = $data['status'] ?? 'active';
    $metal_type = $data['metal_type'] ?? 'gold';

    if ($plan_id) {
        $stmt = $db->prepare("UPDATE lock_in_plans SET plan_name = ?, months = ?, return_percentage = ?, min_investment = ?, max_investment = ?, penalty_percentage = ?, status = ?, metal_type = ? WHERE id = ?");
        $stmt->execute([$plan_name, $months, $return_percentage, $min_invest, $max_invest, $penalty, $status, $metal_type, $plan_id]);
        echo json_encode(['success' => true, 'message' => 'Plan updated successfully']);
    } else {
        $stmt = $db->prepare("INSERT INTO lock_in_plans (plan_name, months, return_percentage, min_investment, max_investment, penalty_percentage, status, metal_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$plan_name, $months, $return_percentage, $min_invest, $max_invest, $penalty, $status, $metal_type]);
        echo json_encode(['success' => true, 'message' => 'Plan created successfully']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
