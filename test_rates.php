<?php
putenv('DB_HOST=localhost');
putenv('DB_NAME=odofasti_gold');
putenv('DB_USER=root');
putenv('DB_PASSWORD=');
require 'config/database.php';
$db = (new Database())->getConnection();
$g = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC, created_at DESC LIMIT 1")->fetchColumn();
$s = $db->query("SELECT rate_per_gram FROM silver_rates ORDER BY rate_date DESC, created_at DESC LIMIT 1")->fetchColumn();
echo json_encode(['gold_rate' => $g, 'silver_rate' => $s]);
