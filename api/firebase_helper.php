<?php
// FIREBASE HELPER (HTTP v1 API)
// TODO: Put your downloaded service account json file in the same folder as this file and name it 'firebase_credentials.json'

function getFCMAccessToken($keyFilePath) {
    if (!file_exists($keyFilePath)) {
        return false;
    }
    
    $keyFile = json_decode(file_get_contents($keyFilePath), true);
    if (!$keyFile) return false;

    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $claim = json_encode([
        'iss' => $keyFile['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => $keyFile['token_uri'],
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));
    $signatureInput = $base64UrlHeader . '.' . $base64UrlClaim;

    $signature = '';
    openssl_sign($signatureInput, $signature, $keyFile['private_key'], 'SHA256');
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $jwt = $signatureInput . '.' . $base64UrlSignature;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $keyFile['token_uri']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($res, true);
    return $tokenData['access_token'] ?? false;
}

function sendFCMNotification($fcmToken, $title, $body, $data = []) {
    if (empty($fcmToken)) return false;

    $keyFilePath = __DIR__ . '/firebase_credentials.json';
    if (!file_exists($keyFilePath)) {
        error_log("firebase_credentials.json not found!");
        return false;
    }

    $keyFile = json_decode(file_get_contents($keyFilePath), true);
    $projectId = $keyFile['project_id'] ?? '';
    
    $accessToken = getFCMAccessToken($keyFilePath);
    if (!$accessToken) return false;

    $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';

    $message = [
        'message' => [
            'token' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => empty($data) ? new stdClass() : $data
        ]
    ];

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}
