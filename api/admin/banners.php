<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

authenticateAdmin();
$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->query("SELECT * FROM banners ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} 
elseif ($method === 'POST') {
    // Handle file upload
    if (!isset($_FILES['banner'])) {
        echo json_encode(['success' => false, 'message' => 'No banner image uploaded']);
        exit;
    }

    $file = $_FILES['banner'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, WEBP allowed.']);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('banner_') . '.' . $ext;
    $uploadDir = __DIR__ . '/../../uploads/banners/';
    
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
        exit;
    }

    $imageUrl = 'uploads/banners/' . $filename;
    
    $stmt = $db->prepare("INSERT INTO banners (image_url, is_active) VALUES (?, 1)");
    $stmt->execute([$imageUrl]);
    
    echo json_encode(['success' => true, 'message' => 'Banner uploaded successfully', 'data' => [
        'id' => $db->lastInsertId(),
        'image_url' => $imageUrl,
        'is_active' => 1
    ]]);
} 
elseif ($method === 'PUT') {
    $id = (int)($_GET['id'] ?? 0);
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['is_active'])) {
        echo json_encode(['success' => false, 'message' => 'is_active field required']);
        exit;
    }

    $stmt = $db->prepare("UPDATE banners SET is_active = ? WHERE id = ?");
    $stmt->execute([$data['is_active'], $id]);
    
    echo json_encode(['success' => true, 'message' => 'Banner status updated']);
} 
elseif ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    // optionally delete file too
    $stmt = $db->prepare("SELECT image_url FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    
    if ($banner) {
        $filePath = __DIR__ . '/../../' . $banner['image_url'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $db->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Banner deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Banner not found']);
    }
}
