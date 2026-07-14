<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/rates.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = (new Database())->getConnection();
    
    // Fetch dynamic rates with markup
    $gold_rate = get_live_rate_with_markup($db, 'gold');
    $silver_rate = get_live_rate_with_markup($db, 'silver');
    
    // Also get change percentages
    $yesterday_gold = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1 OFFSET 1")->fetch();
    $yesterday_silver = $db->query("SELECT rate_per_gram FROM silver_rates ORDER BY rate_date DESC LIMIT 1 OFFSET 1")->fetch();
    
    $gold_change_pct = 0;
    if ($yesterday_gold && $yesterday_gold['rate_per_gram'] > 0) {
        $gold_change_pct = round((($gold_rate - $yesterday_gold['rate_per_gram']) / $yesterday_gold['rate_per_gram']) * 100, 2);
    }
    
    $silver_change_pct = 0;
    if ($yesterday_silver && $yesterday_silver['rate_per_gram'] > 0) {
        $silver_change_pct = round((($silver_rate - $yesterday_silver['rate_per_gram']) / $yesterday_silver['rate_per_gram']) * 100, 2);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'gold' => [
                'rate_per_gram' => $gold_rate,
                'change_percent' => $gold_change_pct
            ],
            'silver' => [
                'rate_per_gram' => $silver_rate,
                'change_percent' => $silver_change_pct
            ]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch rates',
        'error' => $e->getMessage()
    ]);
}
