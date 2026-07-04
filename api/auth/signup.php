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

$name = trim($data['name'] ?? '');
$mobile = trim($data['mobile'] ?? '');
$email = trim($data['email'] ?? '');
$address = trim($data['address'] ?? '');
$city = trim($data['city'] ?? '');
$state = trim($data['state'] ?? '');
$pincode = trim($data['pincode'] ?? '');
$aadhar = trim($data['aadhar_number'] ?? '');
$pan = trim($data['pan_number'] ?? '');
$referral_code = trim($data['referral_code'] ?? '');

// Basic validation
if (empty($name) || empty($mobile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Name and Mobile are required'
    ]);
    exit();
}

if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid mobile number'
    ]);
    exit();
}

$db = (new Database())->getConnection();

// Check if user already exists
$stmt = $db->prepare("SELECT id FROM users WHERE mobile = ?");
$stmt->execute([$mobile]);
if ($stmt->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => 'User already registered with this mobile number'
    ]);
    exit();
}

$referred_by = null;
if (!empty($referral_code)) {
    $refStmt = $db->prepare("SELECT id FROM users WHERE mobile = ?");
    $refStmt->execute([$referral_code]);
    $refUser = $refStmt->fetch();
    if ($refUser) {
        $referred_by = $refUser['id'];
    }
}

try {
    $stmt = $db->prepare("
        INSERT INTO users (
            name, mobile, email, address, city, state, pincode, aadhar_number, pan_number, referred_by, japsan_wallet
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 50)
    ");

    $stmt->execute([
        $name, $mobile, $email, $address, $city, $state, $pincode, $aadhar, $pan, $referred_by
    ]);

    // Sync 50 JC signup bonus with external Japsan Ecosystem
    $jc_api_url = 'https://odofast.in/api/external/wallet_api.php'; 
    $secret = 'JAPSAN_EXTERNAL_API_SECRET_2026';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $jc_api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'action' => 'add',
        'mobile' => $mobile,
        'amount' => 50,
        'description' => 'Signup Bonus on Gold Platform',
        'secret' => $secret
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);

    echo json_encode([
        'success' => true,
        'message' => 'User registered successfully. You can now login with OTP.'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
