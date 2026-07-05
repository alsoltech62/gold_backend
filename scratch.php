<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("DESCRIBE transactions");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
