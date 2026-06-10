<?php
require_once __DIR__ . '/../config/config.php';

function generateJWT($payload) {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;
    $payloadEncoded = base64_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$header.$payloadEncoded", JWT_SECRET, true);
    return "$header.$payloadEncoded." . base64_encode($signature);
}

function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    [$header, $payload, $sig] = $parts;
    $expectedSig = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expectedSig, $sig)) return false;
    $data = json_decode(base64_decode($payload), true);
    if ($data['exp'] < time()) return false;
    return $data;
}

function authenticate() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    $token = substr($authHeader, 7);
    $decoded = verifyJWT($token);
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit();
    }
    return $decoded;
}

function authenticateAdmin() {
    $user = authenticate();
    if (!$user['is_admin']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit();
    }
    return $user;
}

function sendSMS($mobile, $otp) {
    // Mock OTP for development - logs to file instead of sending SMS
    // Remove this block and uncomment a provider below in production
    $logMsg = date('Y-m-d H:i:s') . " OTP for $mobile: $otp\n";
    file_put_contents(__DIR__ . '/../otp_log.txt', $logMsg, FILE_APPEND);
    return true;

    /* === MSG91 ===
    $url = "https://api.msg91.com/api/v5/otp?template_id=" . MSG91_TEMPLATE_ID . "&mobile=91{$mobile}&authkey=" . MSG91_AUTH_KEY . "&otp={$otp}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true)['type'] === 'success';
    */

    /* === Fast2SMS ===
    $ch = curl_init("https://www.fast2sms.com/dev/bulkV2");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode(['variables_values'=>$otp,'route'=>'otp','numbers'=>$mobile]),
        CURLOPT_HTTPHEADER => ['authorization: ' . FAST2SMS_KEY, 'Content-Type: application/json']
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $res['return'] === true;
    */
}
