<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../user/notifications.php';

// This file should ideally be protected or run from CLI only
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'secret_cron_key')) {
    http_response_code(403);
    exit('Forbidden');
}

$type = 'morning';
if (php_sapi_name() === 'cli') {
    foreach ($argv as $arg) {
        if (strpos($arg, 'type=') === 0) {
            $type = explode('=', $arg)[1];
        }
    }
} else {
    $type = $_GET['type'] ?? 'morning';
}

$db = (new Database())->getConnection();

// Fetch all users with FCM tokens
$stmt = $db->query("SELECT id, fcm_token FROM users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
$users = $stmt->fetchAll();

$successCount = 0;
$failCount = 0;

$title = '';
$body = '';

if ($type === 'morning') {
    $title = "Good Morning! ☀️";
    $body = "Start your day by investing in pure 24K Digital Gold. Check today's live rate!";
} else if ($type === 'evening') {
    $title = "Good Evening! 🌙";
    $body = "Secure your future tonight. A small SIP can build huge wealth over time.";
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid type. Use type=morning or type=evening']);
    exit;
}

foreach ($users as $user) {
    $token = $user['fcm_token'];
    
    // Save to database notification history
    $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)")
       ->execute([$user['id'], $title, $body]);
    
    // Send Push Notification
    $result = sendFCMNotification($token, $title, $body);
    
    if ($result) {
        $successCount++;
    } else {
        $failCount++;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Scheduled notifications processed',
    'type' => $type,
    'success_count' => $successCount,
    'fail_count' => $failCount
]);
