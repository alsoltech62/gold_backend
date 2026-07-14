<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../helpers/rates.php';

authenticate();
$db = (new Database())->getConnection();
$rate_val = get_live_rate_with_markup($db, 'gold');

// Since we updated the table, let's get the record to return correct date etc
$rate = $db->query("SELECT * FROM gold_rates ORDER BY rate_date DESC, created_at DESC LIMIT 1")->fetch();
$yesterday = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC, created_at DESC LIMIT 1 OFFSET 1")->fetch();

$change = 0;
$change_pct = 0;
if ($yesterday) {
    $change = $rate_val - $yesterday['rate_per_gram'];
    $change_pct = round(($change / $yesterday['rate_per_gram']) * 100, 2);
}

echo json_encode(['success' => true, 'data' => [
    'rate_per_gram' => (float)$rate_val,
    'rate_date' => $rate ? $rate['rate_date'] : date('Y-m-d'),
    'change' => (float)$change,
    'change_percent' => $change_pct
]]);
