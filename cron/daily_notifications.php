<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/firebase_helper.php';

$db = new Database();
$conn = $db->getConnection();

// Get current hour
date_default_timezone_set('Asia/Kolkata');
$hour = intval(date('H'));

$title = "";
$body = "";

if ($hour == 9) { // 9 AM
    $title = "Good Morning! ☀️";
    $body = "Check today's Gold Rates and start investing.";
} elseif ($hour == 19) { // 7 PM
    $title = "Good Evening! 🌙";
    $body = "Did you check your gold portfolio today? It's growing!";
} else {
    echo "No scheduled notifications for this hour.";
    exit;
}

// Fetch users with fcm_tokens
$stmt = $conn->query("SELECT id, fcm_token FROM users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$successCount = 0;
foreach ($users as $user) {
    if (sendFirebaseNotification($user['fcm_token'], $title, $body, [])) {
        $successCount++;
        
        // Save to notifications table
        $conn->query("INSERT INTO notifications (user_id, title, message) VALUES ({$user['id']}, '$title', '$body')");
    }
}

echo "Sent $successCount scheduled notifications.";
