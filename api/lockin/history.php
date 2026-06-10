<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user = authenticate();

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT ul.*, p.plan_name, p.months, p.return_percentage, p.penalty_percentage
        FROM user_lock_ins ul
        JOIN lock_in_plans p ON ul.plan_id = p.id
        WHERE ul.user_id = ?
        ORDER BY ul.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate progress for each
    $current_time = time();
    foreach ($history as &$h) {
        $start_time = strtotime($h['start_date']);
        $end_time = strtotime($h['end_date']);
        $total_duration = $end_time - $start_time;
        $elapsed = $current_time - $start_time;
        
        $progress = 0;
        if ($total_duration > 0) {
            $progress = ($elapsed / $total_duration) * 100;
        }
        
        $h['progress_percentage'] = min(max($progress, 0), 100);
        $h['days_remaining'] = max(ceil(($end_time - $current_time) / (60 * 60 * 24)), 0);
        
        // Calculate estimated extra gold return based on percentage
        $h['estimated_extra_gold'] = ($h['gold_grams'] * $h['return_percentage']) / 100;
    }

    echo json_encode(['success' => true, 'data' => $history]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch history: ' . $e->getMessage()]);
}
