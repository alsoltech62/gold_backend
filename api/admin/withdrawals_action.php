<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
if (!$user['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $action = $data['action'] ?? '';

    if (!in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("SELECT * FROM withdrawal_requests WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $req = $stmt->fetch();

        if (!$req || $req['status'] !== 'pending') {
            throw new Exception("Invalid or already processed request.");
        }

        if ($action === 'approve') {
            $db->prepare("UPDATE withdrawal_requests SET status = 'approved' WHERE id = ?")->execute([$id]);
            // Update transaction history related to withdrawal to completed
            $db->prepare("UPDATE transactions SET status = 'completed' WHERE user_id = ? AND type = 'sell' AND notes = 'Withdrawal Request' AND status = 'pending' LIMIT 1")->execute([$req['user_id']]);
        } else {
            // Reject and refund
            $db->prepare("UPDATE withdrawal_requests SET status = 'rejected' WHERE id = ?")->execute([$id]);
            $db->prepare("UPDATE users SET inr_wallet = inr_wallet + ? WHERE id = ?")->execute([$req['amount'], $req['user_id']]);
            $db->prepare("UPDATE transactions SET status = 'rejected' WHERE user_id = ? AND type = 'sell' AND notes = 'Withdrawal Request' AND status = 'pending' LIMIT 1")->execute([$req['user_id']]);
        }
        $db->commit();
        echo json_encode(['success' => true, 'message' => "Request {$action}d successfully"]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
