<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query('DESCRIBE users');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
