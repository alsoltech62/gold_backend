<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

authenticateAdmin();
$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = '%' . ($_GET['search'] ?? '') . '%';
    $total  = $db->prepare("SELECT COUNT(*) FROM users WHERE is_admin=0 AND (name LIKE ? OR mobile LIKE ?)");
    $total->execute([$search, $search]);
    $count  = $total->fetchColumn();
    $stmt   = $db->prepare("SELECT u.*, ugs.total_gold_grams, ugs.total_silver_grams, ugs.total_invested_inr FROM users u LEFT JOIN user_summary ugs ON u.id=ugs.user_id WHERE u.is_admin=0 AND (u.name LIKE ? OR u.mobile LIKE ?) ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute([$search, $search]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'pagination' => ['total' => (int)$count, 'page' => $page, 'pages' => ceil($count / $limit)]]);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['mobile']) || empty($data['name'])) {
        echo json_encode(['success' => false, 'message' => 'Name and mobile required']); exit();
    }
    $stmt = $db->prepare("INSERT INTO users (name, mobile, email, address, city, state, pincode, aadhar_number, pan_number, dob) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$data['name'], $data['mobile'], $data['email'] ?? '', $data['address'] ?? '', $data['city'] ?? '', $data['state'] ?? '', $data['pincode'] ?? '', $data['aadhar_number'] ?? '', $data['pan_number'] ?? '', $data['dob'] ?? '']);
    echo json_encode(['success' => true, 'message' => 'Customer created', 'id' => $db->lastInsertId()]);

} elseif ($method === 'PUT') {
    $id   = (int)($_GET['id'] ?? 0);
    $data = json_decode(file_get_contents('php://input'), true);
    $fields = ['name','email','address','city','state','pincode','aadhar_number','pan_number','is_active','dob','bank_name','account_number','ifsc_code','account_holder_name'];
    $updates = []; $values = [];
    foreach ($fields as $f) { if (isset($data[$f])) { $updates[] = "$f=?"; $values[] = $data[$f]; } }
    $values[] = $id;
    $db->prepare("UPDATE users SET " . implode(',', $updates) . " WHERE id=? AND is_admin=0")->execute($values);
    echo json_encode(['success' => true, 'message' => 'Customer updated']);
}
