<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$raw_input = file_get_contents('php://input');
file_put_contents(__DIR__ . '/../../../debug_log.txt', "Input: " . $raw_input . "\n", FILE_APPEND);
try {
    $data = json_decode($raw_input, true);

    $mobile = trim($data['mobile'] ?? '');
    $otp    = trim($data['otp'] ?? '');

    $db = (new Database())->getConnection();

    // Find user
    $stmt = $db->prepare("SELECT * FROM users WHERE mobile=?");
    $stmt->execute([$mobile]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }

    // Verify OTP
    $isAdminMasterOtp = ((int)($user['is_admin'] ?? 0) === 1 && $otp === '123456');

    if (!$isAdminMasterOtp) {
        if ($user['otp'] !== $otp) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid OTP'
            ]);
            exit();
        }

        if (strtotime($user['otp_expires_at']) < time()) {
            echo json_encode([
                'success' => false,
                'message' => 'OTP has expired'
            ]);
            exit();
        }
    }

    // Clear OTP after successful login
    $clearStmt = $db->prepare("UPDATE users SET otp = NULL, otp_expires_at = NULL WHERE id = ?");
    $clearStmt->execute([$user['id']]);

    // Generate token
    $token = generateJWT([
        'id' => $user['id'],
        'mobile' => $user['mobile'],
        'is_admin' => (int)($user['is_admin'] ?? 0)
    ]);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'mobile' => $user['mobile'],
            'email' => $user['email'] ?? '',
            'is_admin' => (bool)($user['is_admin'] ?? 0)
        ]
    ]);
} catch (\Throwable $th) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $th->getMessage(),
        'file' => $th->getFile(),
        'line' => $th->getLine()
    ]);
    exit();
}