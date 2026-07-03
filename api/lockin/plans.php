<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Auth is optional for fetching plans, but since they are in app we can require it
authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $metal_type = isset($_GET['metal_type']) ? $_GET['metal_type'] : null;
    $query = "SELECT * FROM lock_in_plans WHERE status = 'active'";
    $params = [];
    if ($metal_type) {
        $query .= " AND metal_type = ?";
        $params[] = $metal_type;
    }
    $query .= " ORDER BY months ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map color values for frontend based on months if not stored in DB
    $mappedPlans = array_map(function($plan) {
        $color = '#3B82F6'; // Default Blue
        if ($plan['months'] == 12) $color = '#9333EA'; // Purple
        else if ($plan['months'] == 24) $color = '#F97316'; // Orange
        else if ($plan['months'] == 36) $color = '#FFD700'; // Gold
        
        $plan['color_hex'] = $color;
        $plan['returnRate'] = (float) $plan['return_percentage'];
        return $plan;
    }, $plans);

    echo json_encode(['success' => true, 'data' => $mappedPlans]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
