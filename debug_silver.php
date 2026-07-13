<?php
// DB bypassed for test


// Debug Silver Rate
$metal = 'silver';
$symbol = 'XAG';
$url = "https://www.goldapi.io/api/$symbol/INR";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-access-token: goldapi-2647afc42f410ff5680bfec8fc0fda38-io",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Curl Error: $error\n";
echo "Response: $response\n";

if ($httpCode == 200 && $response) {
    $data = json_decode($response, true);
    if (isset($data['price_gram_24k'])) {
        echo "Price Gram 24k: " . $data['price_gram_24k'] . "\n";
    }
    if (isset($data['price'])) {
        echo "Base Price: " . $data['price'] . "\n";
        echo "Calculated per gram: " . ($data['price'] / 31.1034768) . "\n";
    }
}

echo "Testing get_live_rate_with_markup...\n";
// require_once 'api/helpers/rates.php';
// $val = get_live_rate_with_markup($db, 'silver');
// echo "Returned Val: $val\n";

