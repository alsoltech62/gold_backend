<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);

    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$mobile = trim($data['mobile'] ?? '');

// Validate mobile number
if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid mobile number'
    ]);

    exit();
}

$db = (new Database())->getConnection();

// Check if user exists
$checkStmt = $db->prepare("SELECT id FROM users WHERE mobile = ?");
$checkStmt->execute([$mobile]);
$user = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'User not registered. Please signup first.'
    ]);
    exit();
}

// Generate dynamic OTP
$otp = sprintf("%06d", mt_rand(1, 999999));

// Send OTP via BhashSMS
$sms_url = "https://bhashsms.com/api/sendmsg.php?user=Jaherkhabar_sms&pass=123456&sender=JKHABR&phone=" . urlencode($mobile) . "&text=" . urlencode("Your OTP for Jaherkhabar Media Private Limited is {$otp}. It is valid for 10 minutes. Please do not share this OTP with anyone.") . "&priority=ndnd&stype=normal";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $sms_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

$expires = date('Y-m-d H:i:s', time() + OTP_EXPIRY);

// Update user with OTP
$stmt = $db->prepare("
    UPDATE users SET
        otp = ?,
        otp_expires_at = ?
    WHERE mobile = ?
");

$stmt->execute([
    $otp,
    $expires,
    $mobile
]);

// Return success
echo json_encode([
    'success' => true,
    'message' => 'OTP sent successfully'
]);