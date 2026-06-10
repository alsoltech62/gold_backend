<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('delivery_charge', 'package_charge', 'forwarding_charge', 'sip_penalty_charge')");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = floatval($row['setting_value']);
}

// Defaults if not set
$settings['delivery_charge'] = $settings['delivery_charge'] ?? 150;
$settings['package_charge'] = $settings['package_charge'] ?? 50;
$settings['forwarding_charge'] = $settings['forwarding_charge'] ?? 100;

echo json_encode(['success' => true, 'data' => $settings]);
