<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$mobile = trim($data['mobile'] ?? '');
$otp    = trim($data['otp'] ?? '');

// Dummy OTP
if ($otp !== '123456') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid OTP'
    ]);
    exit();
}

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

// Generate token
$token = generateJWT([
    'id' => $user['id'],
    'mobile' => $user['mobile'],
    'is_admin' => (int)$user['is_admin']
]);

echo json_encode([
    'success' => true,
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'mobile' => $user['mobile'],
        'email' => $user['email'] ?? '',
        'is_admin' => (bool)$user['is_admin']
    ]
]);