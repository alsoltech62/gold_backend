<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db   = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT id,name,mobile,email,address,city,state,pincode,aadhar_number,pan_number,dob,profile_photo,bank_name,account_number,ifsc_code,account_holder_name,aadhar_front,aadhar_back,pan_image,created_at FROM users WHERE id=?");
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
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file uploads for KYC
    $upload_dir = __DIR__ . '/../../uploads/kyc/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $updates = [];
    $values = [];
    
    $files = ['aadhar_front', 'aadhar_back', 'pan_image'];
    foreach ($files as $file_field) {
        if (isset($_FILES[$file_field]) && $_FILES[$file_field]['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES[$file_field]['tmp_name'];
            $name = basename($_FILES[$file_field]['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                $new_name = $user['id'] . '_' . $file_field . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $updates[] = "$file_field=?";
                    $values[] = 'uploads/kyc/' . $new_name;
                }
            }
        }
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No valid files uploaded']);
        exit();
    }
    
    $values[] = $user['id'];
    $db->prepare("UPDATE users SET " . implode(',', $updates) . " WHERE id=?")->execute($values);
    echo json_encode(['success' => true, 'message' => 'KYC documents uploaded']);
}
