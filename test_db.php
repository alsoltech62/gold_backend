<?php
require 'config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query('SELECT * FROM sip_plans');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
