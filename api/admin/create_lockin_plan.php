<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../middleware/auth.php';

authenticateAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['months']) || !isset($data['return_percentage'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$months = intval($data['months']);
$return_percentage = floatval($data['return_percentage']);
$metal_type = isset($data['metal_type']) ? $data['metal_type'] : 'gold';

if ($months <= 0 || $return_percentage <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid values']);
    exit;
}

if (!in_array($metal_type, ['gold', 'silver'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid metal type']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("INSERT INTO lock_in_plans (months, return_percentage, metal_type, status) VALUES (?, ?, ?, 'active')");
if ($stmt->execute([$months, $return_percentage, $metal_type])) {
    echo json_encode(['success' => true, 'message' => 'Plan created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create plan']);
}
