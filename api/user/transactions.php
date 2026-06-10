<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // List user transactions
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $type = $_GET['type'] ?? '';

    $where = "WHERE user_id = ?";
    $params = [$user['id']];
    if ($type) {
        $where .= " AND type = ?";
        $params[] = $type;
    }

    $totalStmt = $db->prepare("SELECT COUNT(*) FROM transactions $where");
    $totalStmt->execute($params);
    $total = $totalStmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM transactions $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $transactions,
        'pagination' => [
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new transaction (Buy/Sell/Delivery Request)
    $data = json_decode(file_get_contents('php://input'), true);

    $type = $data['type'] ?? ''; // 'buy', 'sell', 'delivery'
    $amount_inr = isset($data['amount_inr']) ? (float)$data['amount_inr'] : null;
    $gold_grams = isset($data['gold_grams']) ? (float)$data['gold_grams'] : null;
    $payment_method = $data['payment_method'] ?? '';
    $payment_id = $data['payment_id'] ?? '';
    $notes = $data['notes'] ?? '';

    if (!in_array($type, ['buy', 'sell', 'delivery'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction type']);
        exit();
    }

    // Get current gold rate
    $rateQuery = $db->query("SELECT rate_per_gram FROM gold_rates ORDER BY rate_date DESC LIMIT 1")->fetch();
    $current_rate = (float)($rateQuery['rate_per_gram'] ?? 0);

    if ($current_rate <= 0) {
        echo json_encode(['success' => false, 'message' => 'Current gold rate not available']);
        exit();
    }

    // Calculate missing values
    if ($type === 'buy' && $amount_inr > 0) {
        $gold_grams = round($amount_inr / $current_rate, 4);
    } elseif ($type === 'sell' && $gold_grams > 0) {
        $amount_inr = round($gold_grams * $current_rate, 2);
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO transactions (
                user_id, type, amount_inr, gold_grams, gold_rate, 
                status, payment_method, payment_id, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user['id'], $type, $amount_inr, $gold_grams, $current_rate,
            $payment_method, $payment_id, $notes, $user['id']
        ]);

        echo json_encode([
            'success' => true, 
            'message' => ucfirst($type) . ' request submitted successfully',
            'transaction_id' => $db->lastInsertId()
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create transaction: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
