<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
try {
    $db = (new Database())->getConnection();
    $db->exec("ALTER TABLE users ADD COLUMN dob VARCHAR(20) NULL");
    echo "Column dob added successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
