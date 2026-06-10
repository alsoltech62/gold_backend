<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
try {
    $db = (new Database())->getConnection();
    
    // Create lock_in_plans if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS lock_in_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plan_name VARCHAR(100),
        months INT NOT NULL,
        return_percentage DECIMAL(5,2) NOT NULL,
        min_investment DECIMAL(10,2) DEFAULT 1,
        max_investment DECIMAL(10,2),
        penalty_percentage DECIMAL(5,2) DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create user_lock_ins if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS user_lock_ins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        plan_id INT NOT NULL,
        gold_grams DECIMAL(10,4) NOT NULL,
        start_date DATETIME NOT NULL,
        end_date DATETIME NOT NULL,
        status ENUM('active', 'unlocked', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (plan_id) REFERENCES lock_in_plans(id)
    )");

    echo "Tables created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
