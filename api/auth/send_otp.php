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

// Fixed dummy OTP
$otp = '123456';

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
    'message' => 'OTP sent successfully',
    'dev_otp' => $otp
]);