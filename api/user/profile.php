<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db   = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT id,name,mobile,email,address,city,state,pincode,aadhar_number,pan_number,dob,profile_photo,bank_name,account_number,ifsc_code,account_holder_name,created_at FROM users WHERE id=?");
    $stmt->execute([$user['id']]);
    echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $fields = ['name','email','address','city','state','pincode','aadhar_number','pan_number','dob','bank_name','account_number','ifsc_code','account_holder_name'];
    $updates = [];
    $values  = [];
    foreach ($fields as $f) {
        if (isset($data[$f])) { $updates[] = "$f=?"; $values[] = $data[$f]; }
    }
    if (empty($updates)) { echo json_encode(['success' => false, 'message' => 'Nothing to update']); exit(); }
    $values[] = $user['id'];
    $db->prepare("UPDATE users SET " . implode(',', $updates) . " WHERE id=?")->execute($values);
    echo json_encode(['success' => true, 'message' => 'Profile updated']);
}
