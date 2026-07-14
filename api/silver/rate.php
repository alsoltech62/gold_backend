<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/rates.php';

$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // This updates the DB and gets live rate
    $rate_val = get_live_rate_with_markup($db, 'silver');
    
    $stmt = $db->query("
        SELECT id, rate_per_gram, rate_date, created_at 
        FROM silver_rates 
        ORDER BY rate_date DESC, created_at DESC 
        LIMIT 30
    ");
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $current = $history[0] ?? null;
    if ($current) {
        $current['rate_per_gram'] = $rate_val; // Overwrite just in case
    } else {
        $current = ['rate_per_gram' => $rate_val, 'rate_date' => date('Y-m-d')];
    }
    
    echo json_encode([
        'success' => true, 
        'data' => [
            'current_rate' => $current,
            'history' => $history
        ]
    ]);
}
