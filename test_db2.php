<?php
require 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT * FROM user_lock_ins");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
