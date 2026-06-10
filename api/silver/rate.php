<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->query("
        SELECT id, rate_per_gram, rate_date, created_at 
        FROM silver_rates 
        ORDER BY rate_date DESC 
        LIMIT 30
    ");
    $history = $stmt->fetchAll();
    
    $current = $history[0] ?? null;
    
    echo json_encode([
        'success' => true, 
        'data' => [
            'current_rate' => $current,
            'history' => $history
        ]
    ]);
}
