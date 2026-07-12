<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Hardcode the 4 required plans to ensure they are the only ones shown
    $plans = [
        ['id' => 1, 'plan_name' => 'Daily Saver', 'min_amount' => 100, 'frequency' => 'daily'],
        ['id' => 2, 'plan_name' => 'Weekly Saver', 'min_amount' => 500, 'frequency' => 'weekly'],
        ['id' => 3, 'plan_name' => 'Monthly Builder', 'min_amount' => 1000, 'frequency' => 'monthly'],
        ['id' => 4, 'plan_name' => 'Yearly Wealth', 'min_amount' => 10000, 'frequency' => 'yearly']
    ];

    echo json_encode(['success' => true, 'data' => $plans]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
