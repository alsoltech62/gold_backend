<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = authenticate();
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['profile_photo'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['profile_photo'];
$target_dir = __DIR__ . "/../../uploads/profiles/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$file_ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
$allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($file_ext, $allowed_ext)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, WEBP allowed.']);
    exit;
}

$new_filename = "user_" . $user['id'] . "_" . time() . "." . $file_ext;
$target_file = $target_dir . $new_filename;

if (move_uploaded_file($file["tmp_name"], $target_file)) {
    // Update db
    $url = "/uploads/profiles/" . $new_filename;
    $db->prepare("UPDATE users SET profile_photo = ? WHERE id = ?")->execute([$url, $user['id']]);
    echo json_encode(['success' => true, 'message' => 'Profile photo uploaded', 'profile_photo' => $url]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
}
