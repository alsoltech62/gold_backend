<?php
function get_live_rate_with_markup($db, $metal) {
    // 1. Get markup settings
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN (?, ?)");
    $stmt->execute(["{$metal}_markup_type", "{$metal}_markup_value"]);
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $markup_type = $settings["{$metal}_markup_type"] ?? 'fixed';
    $markup_value = (float)($settings["{$metal}_markup_value"] ?? 0);
    
    // 2. Fetch Live Price from GoldAPI (cache for 1 min)
    $cache_file = __DIR__ . "/../../cache/{$metal}_rate.json";
    $live_rate = 0;
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 60) {
        $cache = json_decode(file_get_contents($cache_file), true);
        $live_rate = $cache['rate'] ?? 0;
    } else {
        $symbol = $metal === 'gold' ? 'XAU' : 'XAG';
        $url = "https://www.goldapi.io/api/$symbol/INR";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: goldapi-2647afc42f410ff5680bfec8fc0fda38-io",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $data = json_decode($response, true);
            
            // For silver, sometimes price_gram_24k might not be returned consistently. Let's fallback to price / 31.1035 if needed.
            if (isset($data['price_gram_24k']) && $data['price_gram_24k'] > 0) {
                $live_rate = (float)$data['price_gram_24k'];
            } elseif (isset($data['price']) && $data['price'] > 0) {
                $live_rate = (float)$data['price'] / 31.1034768;
            }
            
            if ($live_rate > 0) {
                if (!is_dir(dirname($cache_file))) {
                    mkdir(dirname($cache_file), 0777, true);
                }
                file_put_contents($cache_file, json_encode(['rate' => $live_rate]));
            }
        }
    }
    
    // Fallback to database if API fails
    if ($live_rate <= 0) {
        $table = $metal === 'gold' ? 'gold_rates' : 'silver_rates';
        try {
            $row = $db->query("SELECT rate_per_gram FROM $table ORDER BY rate_date DESC, created_at DESC LIMIT 1")->fetch();
            return (float)($row['rate_per_gram'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    // 3. Apply Markup
    if ($markup_type === 'percent') {
        $final_rate = $live_rate + ($live_rate * ($markup_value / 100));
    } else {
        $final_rate = $live_rate + $markup_value;
    }
    
    $final_rate = round($final_rate, 2);
    
    // 4. Update the DB so all direct SQL queries get the correct final rate
    $table = $metal === 'gold' ? 'gold_rates' : 'silver_rates';
    $date = date('Y-m-d');
    
    try {
        $updateStmt = $db->prepare("INSERT INTO $table (rate_per_gram, rate_date) VALUES (?, ?) ON DUPLICATE KEY UPDATE rate_per_gram=?, created_at=CURRENT_TIMESTAMP");
        $updateStmt->execute([$final_rate, $date, $final_rate]);
    } catch (Exception $e) {
        // Fallback silently
    }
    
    return $final_rate;
}

function sync_live_rates($db) {
    try {
        get_live_rate_with_markup($db, 'gold');
    } catch(Exception $e) {}
    
    try {
        get_live_rate_with_markup($db, 'silver');
    } catch(Exception $e) {}
}
